<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER, $APPLICATION;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

$siteId = SITE_ID ?: 's1';

/*
 * Базовые группы проекта.
 * R = чтение
 * X = полный доступ
 */
$permissions = [
    '1' => 'X', // Администраторы
];

/*
 * Названия групп, которым надо дать доступ к публичным CRM-страницам.
 */
$groupNamesToAllow = [
    'Менеджер сервисного центра',
    'Механик сервисного центра',
    'Закупщик сервисного центра',
    'Закупщики сервисного центра',
    'Начальник отдела закупок',
    'Начальники закупок',
    'Бухгалтер сервисного центра',
    'Директор сервисного центра',
];

$foundGroups = [];

$by = 'id';
$order = 'asc';

$groupResult = CGroup::GetList($by, $order, []);

while ($group = $groupResult->Fetch()) {
    $groupId = (int)$group['ID'];
    $groupName = trim((string)$group['NAME']);

    if (in_array($groupName, $groupNamesToAllow, true)) {
        $permissions[(string)$groupId] = 'R';

        $foundGroups[] = [
            'ID' => $groupId,
            'NAME' => $groupName,
        ];
    }
}

/*
 * Файлы и разделы, на которые даём чтение.
 */
$paths = [
    '/',
    '/index.php',
    '/crm/',
    '/crm/index.php',
    '/crm/deal/',
    '/crm/deal/index.php',
    '/crm/contact/',
    '/crm/contact/index.php',
    '/crm/type/',
    '/crm/type/index.php',
    '/shop/',
    '/shop/index.php',
];

foreach ($paths as $path) {
    $APPLICATION->SetFileAccessPermission(
        [$siteId, $path],
        $permissions
    );
}

echo '<h2>Права обновлены</h2>';

echo '<pre>';
echo 'SITE_ID = ' . $siteId . PHP_EOL . PHP_EOL;

echo 'Пути:' . PHP_EOL;
foreach ($paths as $path) {
    echo $path . PHP_EOL;
}

echo PHP_EOL;

echo 'Выданы права группам:' . PHP_EOL;
foreach ($permissions as $groupId => $permission) {
    echo 'Группа ID=' . $groupId . ' => ' . $permission . PHP_EOL;
}

echo PHP_EOL;

if (!empty($foundGroups)) {
    echo 'Найдены группы:' . PHP_EOL;

    foreach ($foundGroups as $group) {
        echo 'ID=' . $group['ID'] . ', NAME=' . $group['NAME'] . PHP_EOL;
    }
} else {
    echo 'ВНИМАНИЕ: группы проекта не найдены. Проверь точные названия групп.' . PHP_EOL;
}

echo '</pre>';