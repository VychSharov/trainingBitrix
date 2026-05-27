<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Service\StockSyncService;

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('sharov.servicecenter')) {
    die('Модуль sharov.servicecenter не подключен');
}

try {
    $service = new StockSyncService();
    $result = $service->sync();

    echo '<h2>Синхронизация остатков запчастей</h2>';

    foreach ($result as $row) {
        echo '<hr>';

        echo '<b>' . htmlspecialcharsbx((string)$row['name']) . '</b><br>';

        if (!empty($row['productId'])) {
            echo 'ID товара/предложения: ' . (int)$row['productId'] . '<br>';
        }

        if (array_key_exists('externalQuantity', $row)) {
            echo 'Остаток из внешнего сервиса: ' . (int)$row['externalQuantity'] . '<br>';
        }

        if (array_key_exists('realQuantity', $row)) {
            echo 'Остаток в каталоге: ' . (float)$row['realQuantity'] . '<br>';
        }

        if (!empty($row['purchaseId'])) {
            echo 'Создана заявка на закупку ID: ' . (int)$row['purchaseId'] . '<br>';
        }

        echo 'Результат: '
            . (!empty($row['success'])
                ? '<span style="color:green;">OK</span>'
                : '<span style="color:red;">Ошибка</span>')
            . '<br>';

        echo 'Сообщение: ' . htmlspecialcharsbx((string)$row['message']) . '<br>';
    }

    echo '<hr>Готово.';
} catch (Throwable $exception) {
    echo '<span style="color:red;">Ошибка: '
        . htmlspecialcharsbx($exception->getMessage())
        . '</span>';
}