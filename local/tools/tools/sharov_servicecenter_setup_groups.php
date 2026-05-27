<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER, $APPLICATION;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

$groups = [
    [
        'NAME' => 'Менеджеры сервисного центра',
        'STRING_ID' => 'SERVICECENTER_MANAGERS',
        'DESCRIPTION' => 'Менеджеры автосервиса. Видят все сервисные сделки и гараж клиентов.',
    ],
    [
        'NAME' => 'Директор сервисного центра',
        'STRING_ID' => 'SERVICECENTER_DIRECTOR',
        'DESCRIPTION' => 'Директор автосервиса. Полный доступ.',
    ],
];

echo '<h2>Настройка групп сервисного центра</h2>';

foreach ($groups as $groupData) {
    $groupResult = CGroup::GetList(
        $by = 'id',
        $order = 'asc',
        [
            'STRING_ID' => $groupData['STRING_ID'],
        ]
    );

    $existingGroup = $groupResult->Fetch();

    if ($existingGroup) {
        echo '<hr>';
        echo 'Группа уже существует: <b>' . htmlspecialcharsbx($groupData['NAME']) . '</b><br>';
        echo 'ID: ' . (int)$existingGroup['ID'] . '<br>';
        echo 'STRING_ID: ' . htmlspecialcharsbx($existingGroup['STRING_ID']) . '<br>';
        continue;
    }

    $group = new CGroup();

    $groupId = $group->Add([
        'ACTIVE' => 'Y',
        'NAME' => $groupData['NAME'],
        'STRING_ID' => $groupData['STRING_ID'],
        'DESCRIPTION' => $groupData['DESCRIPTION'],
    ]);

    echo '<hr>';

    if ($groupId > 0) {
        echo '<span style="color:green;">Создана группа:</span> <b>'
            . htmlspecialcharsbx($groupData['NAME'])
            . '</b><br>';
        echo 'ID: ' . (int)$groupId . '<br>';
        echo 'STRING_ID: ' . htmlspecialcharsbx($groupData['STRING_ID']) . '<br>';
    } else {
        $exception = $APPLICATION->GetException();

        echo '<span style="color:red;">Ошибка создания группы '
            . htmlspecialcharsbx($groupData['NAME'])
            . ': '
            . ($exception ? htmlspecialcharsbx($exception->GetString()) : 'неизвестная ошибка')
            . '</span><br>';
    }
}

echo '<hr>Готово.';