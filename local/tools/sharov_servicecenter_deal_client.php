<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    if (!Loader::includeModule('crm')) {
        throw new RuntimeException('Модуль CRM не подключен');
    }

    $dealId = (int)($_REQUEST['dealId'] ?? 0);

    if ($dealId <= 0) {
        throw new RuntimeException('Не указан ID сделки');
    }

    $dealResult = CCrmDeal::GetListEx(
        [],
        [
            '=ID' => $dealId,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        false,
        [
            'ID',
            'TITLE',
            'CONTACT_ID',
        ]
    );

    $deal = $dealResult->Fetch();

    if (!$deal) {
        throw new RuntimeException('Сделка не найдена');
    }

    echo Json::encode([
        'success' => true,
        'dealId' => $dealId,
        'contactId' => (int)$deal['CONTACT_ID'],
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}