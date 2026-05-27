<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;

global $USER, $DB;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('iblock')) {
    die('Модуль iblock не подключен');
}

if (!Loader::includeModule('catalog')) {
    die('Модуль catalog не подключен');
}

if (!Loader::includeModule('crm')) {
    die('Модуль crm не подключен');
}

Loader::includeModule('im');
Loader::includeModule('sharov.servicecenter');

$purchaseEntityTypeId = 1046;
$partsIblockId = 15;

$partNames = [
    'Масляный фильтр',
    'Воздушный фильтр',
    'Свеча зажигания',
];

echo '<h2>Тест синхронизации остатков запчастей</h2>';

/**
 * Получает случайный остаток из внешнего сервиса.
 * Если random.org недоступен, используем random_int для теста.
 *
 * @return int
 */
function sharovGetExternalStockQuantity()
{
    $url = 'https://www.random.org/integers/?num=1&min=0&max=10&col=1&base=10&format=plain&rnd=new';

    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);

    if ($result === false) {
        return random_int(0, 10);
    }

    return (int)trim($result);
}

/**
 * Ищет товар по точному названию.
 *
 * @param string $name
 * @return array|null
 */
function sharovFindProductByName($name, $iblockId)
{
    $elementResult = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            '=IBLOCK_ID' => $iblockId,
            '=NAME' => $name,
            'ACTIVE' => 'Y',
        ],
        false,
        ['nTopCount' => 1],
        [
            'ID',
            'IBLOCK_ID',
            'NAME',
        ]
    );

    $element = $elementResult->Fetch();

    return $element ?: null;
}

/**
 * Обновляет количество товара.
 *
 * @param int $productId
 * @param float $quantity
 * @return bool
 */
function sharovUpdateProductQuantity($productId, $quantity)
{
    global $APPLICATION;

    $productId = (int)$productId;
    $quantity = (float)$quantity;

    $product = CCatalogProduct::GetByID($productId);

    if ($product) {
        $result = CCatalogProduct::Update($productId, [
            'QUANTITY' => $quantity,
            'QUANTITY_TRACE' => 'N',
            'CAN_BUY_ZERO' => 'Y',
        ]);
    } else {
        $result = CCatalogProduct::Add([
            'ID' => $productId,
            'QUANTITY' => $quantity,
            'QUANTITY_TRACE' => 'N',
            'CAN_BUY_ZERO' => 'Y',
        ]);
    }

    if (!$result) {
        $exception = $APPLICATION->GetException();

        throw new RuntimeException(
            'Не удалось обновить остаток товара ID=' . $productId . '. '
            . ($exception ? $exception->GetString() : 'Ошибка неизвестна')
        );
    }

    /*
     * Если включён складской учёт, дополнительно обновим первый активный склад.
     */
    if (class_exists('CCatalogStore') && class_exists('CCatalogStoreProduct')) {
        $storeResult = CCatalogStore::GetList(
            ['ID' => 'ASC'],
            ['ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'TITLE', 'ADDRESS']
        );

        $store = $storeResult->Fetch();

        if ($store) {
            $storeId = (int)$store['ID'];

            $storeAmountResult = CCatalogStoreProduct::GetList(
                [],
                [
                    'PRODUCT_ID' => $productId,
                    'STORE_ID' => $storeId,
                ],
                false,
                false,
                [
                    'ID',
                    'AMOUNT',
                ]
            );

            $storeAmount = $storeAmountResult->Fetch();

            if ($storeAmount) {
                CCatalogStoreProduct::Update(
                    (int)$storeAmount['ID'],
                    [
                        'AMOUNT' => $quantity,
                    ]
                );
            } else {
                CCatalogStoreProduct::Add([
                    'PRODUCT_ID' => $productId,
                    'STORE_ID' => $storeId,
                    'AMOUNT' => $quantity,
                ]);
            }
        }
    }

    $updatedProduct = CCatalogProduct::GetByID($productId);

    if (!$updatedProduct) {
        throw new RuntimeException('После обновления товар ID=' . $productId . ' не найден в каталоге');
    }

    return (float)$updatedProduct['QUANTITY'];
}

/**
 * Ищет stage id смарт-процесса по названию стадии.
 *
 * @param int $entityTypeId
 * @param string $stageName
 * @return string
 */
function sharovFindDynamicStageId($entityTypeId, $stageName)
{
    global $DB;

    $entityTypeIdSql = $DB->ForSql((string)$entityTypeId);
    $stageNameSql = $DB->ForSql($stageName);

    $result = $DB->Query("
        SELECT STATUS_ID, NAME, ENTITY_ID
        FROM b_crm_status
        WHERE NAME = '{$stageNameSql}'
          AND (
              STATUS_ID LIKE 'DT{$entityTypeIdSql}_%'
              OR ENTITY_ID LIKE '%{$entityTypeIdSql}%'
              OR ENTITY_ID LIKE 'DYNAMIC_%'
          )
        ORDER BY SORT ASC
        LIMIT 1
    ");

    $row = $result->Fetch();

    return $row ? (string)$row['STATUS_ID'] : '';
}

/**
 * Возвращает первого закупщика из группы.
 *
 * @return int
 */
function sharovGetPurchaserUserId()
{
    $groupCodes = [
        'SERVICECENTER_PURCHASERS',
        'SERVICECENTER_PURCHASE_HEAD',
    ];

    foreach ($groupCodes as $groupCode) {
        $groupResult = CGroup::GetList(
            $by = 'id',
            $order = 'asc',
            [
                'STRING_ID' => $groupCode,
            ]
        );

        $group = $groupResult->Fetch();

        if (!$group) {
            continue;
        }

        $userResult = CUser::GetList(
            $by = 'id',
            $order = 'asc',
            [
                'ACTIVE' => 'Y',
                'GROUPS_ID' => [(int)$group['ID']],
            ],
            [
                'FIELDS' => [
                    'ID',
                    'NAME',
                    'LAST_NAME',
                    'LOGIN',
                ],
            ]
        );

        $user = $userResult->Fetch();

        if ($user) {
            return (int)$user['ID'];
        }
    }

    global $USER;

    return (int)$USER->GetID();
}

/**
 * Отправляет уведомление закупщику.
 *
 * @param int $toUserId
 * @param string $message
 * @return void
 */
function sharovNotifyUser($toUserId, $message)
{
    if ($toUserId <= 0) {
        return;
    }

    if (class_exists('CIMNotify')) {
        CIMNotify::Add([
            'TO_USER_ID' => $toUserId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'sharov.servicecenter',
            'NOTIFY_EVENT' => 'purchase_auto',
            'NOTIFY_MESSAGE' => $message,
        ]);
    }
}

/**
 * Создаёт заявку на закупку в смарт-процессе.
 *
 * @param int $entityTypeId
 * @param int $productId
 * @param string $productName
 * @param int $quantity
 * @param int $purchaserId
 * @return int
 */
function sharovCreateAutoPurchaseRequest($entityTypeId, $productId, $productName, $quantity, $purchaserId)
{
    global $USER;

    $factory = Container::getInstance()->getFactory($entityTypeId);

    if (!$factory) {
        throw new RuntimeException('Не найдена фабрика смарт-процесса entityTypeId=' . $entityTypeId);
    }

    $item = $factory->createItem();

    $item->setTitle('Автозакупка: ' . $productName);

    $item->set('UF_SC_REQUESTER_ID', (int)$USER->GetID());
    $item->set('UF_SC_APPROVER_ID', $purchaserId);
    $item->set('UF_SC_AUTO_CREATED', 1);
    $item->set('UF_SC_SOURCE_PRODUCT_ID', $productId);
    $item->set('UF_SC_QUANTITY', $quantity);

    if ($purchaserId > 0) {
        $item->setAssignedById($purchaserId);
    }

    $doneStageId = sharovFindDynamicStageId($entityTypeId, 'Выполнено');

    if ($doneStageId !== '') {
        $item->setStageId($doneStageId);
    }

    $operation = $factory->getAddOperation($item);
    $result = $operation->launch();

    if (!$result->isSuccess()) {
        throw new RuntimeException(implode('; ', $result->getErrorMessages()));
    }

    return (int)$item->getId();
}

$purchaserId = sharovGetPurchaserUserId();

echo 'Закупщик для уведомлений: userId=' . $purchaserId . '<br><br>';

foreach ($partNames as $partName) {
    echo '<hr>';
    echo '<b>Запчасть: ' . htmlspecialcharsbx($partName) . '</b><br>';

    try {
        $product = sharovFindProductByName($partName, $partsIblockId);

        if (!$product) {
            echo '<span style="color:red;">Товар не найден</span><br>';
            continue;
        }

        $productId = (int)$product['ID'];

        $externalQuantity = sharovGetExternalStockQuantity();

        echo 'ID товара: ' . $productId . '<br>';
        echo 'Внешний сервис вернул остаток: <b>' . $externalQuantity . '</b><br>';

        if ($externalQuantity > 0) {
            $realQuantity = sharovUpdateProductQuantity($productId, $externalQuantity);

            echo '<span style="color:green;">Остаток обновлён. Внешний сервис: '
                . $externalQuantity
                . ', в каталоге стало: '
                . $realQuantity
                . '</span><br>';
            continue;
        }

        echo '<span style="color:red;">Остаток = 0. Создаём автозакупку 10 шт.</span><br>';

        $purchaseId = sharovCreateAutoPurchaseRequest(
            $purchaseEntityTypeId,
            $productId,
            $partName,
            10,
            $purchaserId
        );

        $realQuantity = sharovUpdateProductQuantity($productId, 10);

        sharovNotifyUser(
            $purchaserId,
            'Запчасть "' . $partName . '" закончилась. Автоматически создана закупка 10 шт. Заявка #' . $purchaseId . ' выполнена.'
        );

        echo '<span style="color:green;">Создана заявка на закупку ID=' . $purchaseId . '</span><br>';
        echo '<span style="color:green;">Остаток товара установлен: ' . $realQuantity . '</span><br>';
    } catch (Throwable $exception) {
        echo '<span style="color:red;">Ошибка: '
            . htmlspecialcharsbx($exception->getMessage())
            . '</span><br>';
    }
}

echo '<hr>Готово.';