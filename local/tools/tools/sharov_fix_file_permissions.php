<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER, $APPLICATION;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

/*
 * ID сайта. Обычно s1.
 */
$siteId = SITE_ID ?: 's1';

/*
 * 1 — администраторы
 * 2 — все зарегистрированные пользователи
 *
 * R = чтение
 * W = запись
 * X = полный доступ
 */
$permissions = [
    '1' => 'X',
    '20' => 'R',
    '21' => 'R',
    '22' => 'R',
    '23' => 'R',
    '24' => 'R',

];

/*
 * Даём чтение на корень сайта.
 */
$APPLICATION->SetFileAccessPermission(
    [$siteId, '/'],
    $permissions
);

/*
 * И отдельно на index.php, потому что ошибка именно по нему.
 */
$APPLICATION->SetFileAccessPermission(
    [$siteId, '/index.php'],
    $permissions
);

echo 'Права выданы: группа 2 получила чтение на / и /index.php';