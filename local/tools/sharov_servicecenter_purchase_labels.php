<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    if (!Loader::includeModule('crm')) {
        throw new RuntimeException('Модуль crm не подключен');
    }

    if (!Loader::includeModule('iblock')) {
        throw new RuntimeException('Модуль iblock не подключен');
    }

    if (!Loader::includeModule('catalog')) {
        throw new RuntimeException('Модуль catalog не подключен');
    }

    $entityTypeId = 1046;
    $itemId = (int)($_REQUEST['itemId'] ?? 0);

    if ($itemId <= 0) {
        throw new RuntimeException('Не указан ID заявки');
    }

    $factory = Container::getInstance()->getFactory($entityTypeId);

    if (!$factory) {
        throw new RuntimeException('Не найдена фабрика смарт-процесса ' . $entityTypeId);
    }

    $item = $factory->getItem($itemId);

    if (!$item) {
        throw new RuntimeException('Заявка не найдена');
    }

    $data = $item->getData();

    $productId = (int)($data['UF_SC_SOURCE_PRODUCT_ID'] ?? 0);
    $requesterId = (int)($data['UF_SC_REQUESTER_ID'] ?? 0);
    $approverId = (int)($data['UF_SC_APPROVER_ID'] ?? 0);

    $productLabel = '';
    $requesterLabel = '';
    $approverLabel = '';

    if ($productId > 0) {
        $elementResult = CIBlockElement::GetList(
            [],
            [
                '=ID' => $productId,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
            ]
        );

        $element = $elementResult->Fetch();

        if ($element) {
            $product = CCatalogProduct::GetByID($productId);
            $quantity = $product ? (float)$product['QUANTITY'] : 0;

            $productLabel = $element['NAME'] . ' — остаток: ' . $quantity;
        }
    }

    /**
     * Возвращает имя пользователя.
     *
     * @param int $userId
     * @return string
     */
    function sharovGetUserLabelById(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $userResult = CUser::GetByID($userId);
        $user = $userResult->Fetch();

        if (!$user) {
            return '';
        }

        $label = trim(
            (string)$user['LAST_NAME']
            . ' '
            . (string)$user['NAME']
            . ' '
            . (string)$user['SECOND_NAME']
        );

        if ($label === '') {
            $label = (string)$user['LOGIN'];
        }

        return $label;
    }

    $requesterLabel = sharovGetUserLabelById($requesterId);
    $approverLabel = sharovGetUserLabelById($approverId);

    echo Json::encode([
        'success' => true,
        'itemId' => $itemId,

        'productId' => $productId,
        'productLabel' => $productLabel,

        'requesterId' => $requesterId,
        'requesterLabel' => $requesterLabel,

        'approverId' => $approverId,
        'approverLabel' => $approverLabel,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}