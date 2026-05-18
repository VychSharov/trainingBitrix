<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    $role = (string)($_REQUEST['role'] ?? 'requester');

    /**
     * Форматирует имя пользователя.
     *
     * @param array $user
     * @return string
     */
    function sharovFormatUserName(array $user): string
    {
        $name = trim(
            (string)$user['LAST_NAME']
            . ' '
            . (string)$user['NAME']
            . ' '
            . (string)$user['SECOND_NAME']
        );

        if ($name === '') {
            $name = (string)$user['LOGIN'];
        }

        if (!empty($user['EMAIL'])) {
            $name .= ' (' . $user['EMAIL'] . ')';
        }

        return $name;
    }

    /**
     * Возвращает пользователей группы по символьному коду.
     *
     * @param string $groupCode
     * @return array
     */
    function sharovGetUsersByGroupCode(string $groupCode): array
    {
        $groupResult = CGroup::GetList(
            $by = 'id',
            $order = 'asc',
            [
                'STRING_ID' => $groupCode,
            ]
        );

        $group = $groupResult->Fetch();

        if (!$group) {
            return [];
        }

        $users = [];

        $userResult = CUser::GetList(
            $by = 'last_name',
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
                    'SECOND_NAME',
                    'LOGIN',
                    'EMAIL',
                ],
            ]
        );

        while ($user = $userResult->Fetch()) {
            $users[] = [
                'id' => (int)$user['ID'],
                'label' => sharovFormatUserName($user),
            ];
        }

        return $users;
    }

    /**
     * Возвращает всех активных пользователей.
     *
     * @return array
     */
    function sharovGetActiveUsers(): array
    {
        $users = [];

        $userResult = CUser::GetList(
            $by = 'last_name',
            $order = 'asc',
            [
                'ACTIVE' => 'Y',
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
            $users[] = [
                'id' => (int)$user['ID'],
                'label' => sharovFormatUserName($user),
            ];
        }

        return $users;
    }

    if ($role === 'approver') {
        $users = sharovGetUsersByGroupCode('SERVICECENTER_PURCHASERS');

        if (empty($users)) {
            $users = sharovGetUsersByGroupCode('SERVICECENTER_PURCHASE_HEAD');
        }

        echo Json::encode([
            'success' => true,
            'role' => $role,
            'currentUserId' => (int)$USER->GetID(),
            'users' => $users,
        ]);

        die();
    }

    echo Json::encode([
        'success' => true,
        'role' => $role,
        'currentUserId' => (int)$USER->GetID(),
        'users' => sharovGetActiveUsers(),
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}