<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Service\PurchaseRequestService;

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('sharov.servicecenter')) {
    die('Модуль sharov.servicecenter не подключен');
}

try {
    $service = new PurchaseRequestService();
    $result = $service->processPending();

    echo '<h2>Обработка ручных заявок на закупку</h2>';

    if (empty($result)) {
        echo 'Нет заявок для обработки.';
        die();
    }

    foreach ($result as $row) {
        echo '<hr>';

        if (!empty($row['itemId'])) {
            echo 'Заявка ID: ' . (int)$row['itemId'] . '<br>';
        }

        if (!empty($row['productName'])) {
            echo 'Запчасть: ' . htmlspecialcharsbx((string)$row['productName']) . '<br>';
        }

        if (array_key_exists('quantity', $row)) {
            echo 'Количество: ' . (float)$row['quantity'] . '<br>';
        }

        if (array_key_exists('newQuantity', $row)) {
            echo 'Новый остаток: ' . (float)$row['newQuantity'] . '<br>';
        }

        echo 'Результат: '
            . (!empty($row['success'])
                ? '<span style="color:green;">OK</span>'
                : '<span style="color:red;">Ошибка</span>')
            . '<br>';

        echo 'Сообщение: ' . htmlspecialcharsbx((string)$row['message']) . '<br>';
    }
} catch (Throwable $exception) {
    echo '<span style="color:red;">Ошибка: '
        . htmlspecialcharsbx($exception->getMessage())
        . '</span>';
}