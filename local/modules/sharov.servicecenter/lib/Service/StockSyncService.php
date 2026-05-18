<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use RuntimeException;
use Throwable;

class StockSyncService
{
    private const PARTS_IBLOCK_ID = 15;
    private const PURCHASE_ENTITY_TYPE_ID = 1046;

    private const PART_NAMES = [
        'Масляный фильтр',
        'Воздушный фильтр',
        'Свеча зажигания',
    ];

    /**
     * Запускает синхронизацию остатков запчастей.
     *
     * @return array
     */
    public function sync(): array
    {
        $this->includeModules();

        $result = [];
        $purchaserId = $this->getPurchaserUserId();

        foreach (self::PART_NAMES as $partName) {
            try {
                $product = $this->findProductByName($partName);

                if (!$product) {
                    $result[] = [
                        'name' => $partName,
                        'success' => false,
                        'message' => 'Товар не найден',
                    ];

                    continue;
                }

                $productId = (int)$product['ID'];
                $externalQuantity = $this->getExternalStockQuantity();

                if ($externalQuantity > 0) {
                    $realQuantity = $this->updateProductQuantity($productId, $externalQuantity);

                    $result[] = [
                        'name' => $partName,
                        'productId' => $productId,
                        'externalQuantity' => $externalQuantity,
                        'realQuantity' => $realQuantity,
                        'success' => true,
                        'message' => 'Остаток обновлён',
                    ];

                    continue;
                }

                $purchaseId = $this->createAutoPurchaseRequest(
                    $productId,
                    $partName,
                    10,
                    $purchaserId
                );

                $realQuantity = $this->updateProductQuantity($productId, 10);

                $this->notifyUser(
                    $purchaserId,
                    'Запчасть "' . $partName . '" закончилась. '
                    . 'Автоматически создана закупка 10 шт. '
                    . 'Заявка #' . $purchaseId . ' выполнена.'
                );

                $result[] = [
                    'name' => $partName,
                    'productId' => $productId,
                    'externalQuantity' => 0,
                    'realQuantity' => $realQuantity,
                    'purchaseId' => $purchaseId,
                    'success' => true,
                    'message' => 'Остаток был 0. Создана автозакупка 10 шт.',
                ];
            } catch (Throwable $exception) {
                $result[] = [
                    'name' => $partName,
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Подключает нужные модули.
     *
     * @return void
     */
    private function includeModules(): void
    {
        foreach (['iblock', 'catalog', 'crm'] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                throw new RuntimeException('Модуль ' . $moduleId . ' не подключен');
            }
        }

        Loader::includeModule('im');
    }

    /**
     * Получает остаток из внешнего сервиса.
     *
     * @return int
     */
    private function getExternalStockQuantity(): int
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
     * Ищет торговое предложение запчасти по названию.
     *
     * @param string $name
     * @return array|null
     */
    private function findProductByName(string $name): ?array
    {
        $elementResult = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                '=IBLOCK_ID' => self::PARTS_IBLOCK_ID,
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
     * Обновляет остаток товара в каталоге и на первом активном складе.
     *
     * @param int $productId
     * @param float $quantity
     * @return float
     */
    private function updateProductQuantity(int $productId, float $quantity): float
    {
        global $APPLICATION;

        $product = \CCatalogProduct::GetByID($productId);

        if ($product) {
            $updateResult = \CCatalogProduct::Update($productId, [
                'QUANTITY' => $quantity,
                'QUANTITY_TRACE' => 'N',
                'CAN_BUY_ZERO' => 'Y',
            ]);
        } else {
            $updateResult = \CCatalogProduct::Add([
                'ID' => $productId,
                'QUANTITY' => $quantity,
                'QUANTITY_TRACE' => 'N',
                'CAN_BUY_ZERO' => 'Y',
            ]);
        }

        if (!$updateResult) {
            $exception = $APPLICATION->GetException();

            throw new RuntimeException(
                'Не удалось обновить остаток товара ID=' . $productId . '. '
                . ($exception ? $exception->GetString() : 'Ошибка неизвестна')
            );
        }

        $this->updateFirstStoreAmount($productId, $quantity);

        $updatedProduct = \CCatalogProduct::GetByID($productId);

        if (!$updatedProduct) {
            throw new RuntimeException('После обновления товар ID=' . $productId . ' не найден');
        }

        return (float)$updatedProduct['QUANTITY'];
    }

    /**
     * Обновляет остаток на первом активном складе.
     *
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    private function updateFirstStoreAmount(int $productId, float $quantity): void
    {
        if (!class_exists('\CCatalogStore') || !class_exists('\CCatalogStoreProduct')) {
            return;
        }

        $storeResult = \CCatalogStore::GetList(
            ['ID' => 'ASC'],
            ['ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'TITLE']
        );

        $store = $storeResult->Fetch();

        if (!$store) {
            return;
        }

        $storeId = (int)$store['ID'];

        $storeAmountResult = \CCatalogStoreProduct::GetList(
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
            \CCatalogStoreProduct::Update(
                (int)$storeAmount['ID'],
                [
                    'AMOUNT' => $quantity,
                ]
            );

            return;
        }

        \CCatalogStoreProduct::Add([
            'PRODUCT_ID' => $productId,
            'STORE_ID' => $storeId,
            'AMOUNT' => $quantity,
        ]);
    }

    /**
     * Создаёт автоматическую заявку на закупку.
     *
     * @param int $productId
     * @param string $productName
     * @param int $quantity
     * @param int $purchaserId
     * @return int
     */
    private function createAutoPurchaseRequest(
        int $productId,
        string $productName,
        int $quantity,
        int $purchaserId
    ): int {
        global $USER;

        $factory = Container::getInstance()->getFactory(self::PURCHASE_ENTITY_TYPE_ID);

        if (!$factory) {
            throw new RuntimeException(
                'Не найдена фабрика смарт-процесса entityTypeId=' . self::PURCHASE_ENTITY_TYPE_ID
            );
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

        $doneStageId = $this->findDynamicStageId('Выполнено');

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

    /**
     * Ищет стадию смарт-процесса по названию.
     *
     * @param string $stageName
     * @return string
     */
    private function findDynamicStageId(string $stageName): string
    {
        global $DB;

        $stageNameSql = $DB->ForSql($stageName);
        $entityTypeIdSql = $DB->ForSql((string)self::PURCHASE_ENTITY_TYPE_ID);

        $result = $DB->Query("
            SELECT STATUS_ID
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
     * Возвращает первого закупщика.
     *
     * @return int
     */
    private function getPurchaserUserId(): int
    {
        $groupCodes = [
            'SERVICECENTER_PURCHASERS',
            'SERVICECENTER_PURCHASE_HEAD',
        ];

        foreach ($groupCodes as $groupCode) {
            $groupResult = \CGroup::GetList(
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

            $userResult = \CUser::GetList(
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
     * Отправляет уведомление пользователю.
     *
     * @param int $toUserId
     * @param string $message
     * @return void
     */
    private function notifyUser(int $toUserId, string $message): void
    {
        if ($toUserId <= 0) {
            return;
        }

        if (!class_exists('\CIMNotify')) {
            return;
        }

        \CIMNotify::Add([
            'TO_USER_ID' => $toUserId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'sharov.servicecenter',
            'NOTIFY_EVENT' => 'purchase_auto',
            'NOTIFY_MESSAGE' => $message,
        ]);
    }
}