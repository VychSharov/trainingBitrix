<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Sharov\ServiceCenter\Service\ServicePartProvider;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    if (!Loader::includeModule('sharov.servicecenter')) {
        throw new RuntimeException('Модуль sharov.servicecenter не подключен');
    }

    $parts = (new ServicePartProvider())->getParts();

    echo Json::encode([
        'success' => true,
        'parts' => $parts,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}