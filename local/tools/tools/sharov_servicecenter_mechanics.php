<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    $groupCode = 'SERVICECENTER_MECHANICS';

    $groupResult = CGroup::GetList(
        $by = 'id',
        $order = 'asc',
        [
            'STRING_ID' => $groupCode,
        ]
    );

    $group = $groupResult->Fetch();

    if (!$group) {
        throw new RuntimeException('Не найдена группа механиков SERVICECENTER_MECHANICS');
    }

    $groupId = (int)$group['ID'];

    $users = [];

    $by = 'last_name';
    $order = 'asc';

    $userResult = CUser::GetList(
        $by,
        $order,
        [
            'ACTIVE' => 'Y',
            'GROUPS_ID' => [$groupId],
        ],
        [
            'FIELDS' => [
                'ID',
                'NAME',
                'LAST_NAME',
                'SECOND_NAME',
                'LOGIN',
                'EMAIL',
            ],
        ]
    );

    while ($user = $userResult->Fetch()) {
        $name = trim(
            $user['LAST_NAME']
            . ' '
            . $user['NAME']
            . ' '
            . $user['SECOND_NAME']
        );

        if ($name === '') {
            $name = $user['LOGIN'];
        }

        $users[] = [
            'id' => (int)$user['ID'],
            'label' => $name,
        ];
    }

    echo Json::encode([
        'success' => true,
        'mechanics' => $users,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}