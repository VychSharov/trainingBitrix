<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    $userId = (int)($_REQUEST['userId'] ?? 0);

    if ($userId <= 0) {
        throw new RuntimeException('Не указан пользователь');
    }

    $userResult = CUser::GetByID($userId);
    $user = $userResult->Fetch();

    if (!$user) {
        throw new RuntimeException('Пользователь не найден');
    }

    $label = trim(
        $user['LAST_NAME']
        . ' '
        . $user['NAME']
        . ' '
        . $user['SECOND_NAME']
    );

    if ($label === '') {
        $label = $user['LOGIN'];
    }

    echo Json::encode([
        'success' => true,
        'id' => $userId,
        'label' => $label,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}