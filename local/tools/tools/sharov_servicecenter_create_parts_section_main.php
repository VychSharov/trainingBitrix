<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER, $APPLICATION;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('iblock')) {
    die('Модуль iblock не подключен');
}

$iblockId = 14;
$sectionName = 'Запчасти сервисного центра';
$sectionCode = 'SERVICECENTER_PARTS';

echo '<h2>Создание раздела запчастей в основном каталоге</h2>';

$parentSectionId = 0;

$parentResult = CIBlockSection::GetList(
    [],
    [
        'IBLOCK_ID' => $iblockId,
        '=NAME' => 'Товары',
    ],
    false,
    [
        'ID',
        'NAME',
    ]
);

$parent = $parentResult->Fetch();

if ($parent) {
    $parentSectionId = (int)$parent['ID'];
}

$sectionResult = CIBlockSection::GetList(
    [],
    [
        'IBLOCK_ID' => $iblockId,
        '=CODE' => $sectionCode,
    ],
    false,
    [
        'ID',
        'NAME',
        'CODE',
    ]
);

$section = $sectionResult->Fetch();

if ($section) {
    echo '<span style="color:green;">Раздел уже существует</span><br>';
    echo 'ID = ' . (int)$section['ID'] . '<br>';
    echo 'NAME = ' . htmlspecialcharsbx($section['NAME']) . '<br>';
    echo 'CODE = ' . htmlspecialcharsbx($section['CODE']) . '<br>';
    die();
}

$sectionObject = new CIBlockSection();

$fields = [
    'IBLOCK_ID' => $iblockId,
    'ACTIVE' => 'Y',
    'NAME' => $sectionName,
    'CODE' => $sectionCode,
    'SORT' => 100,
];

if ($parentSectionId > 0) {
    $fields['IBLOCK_SECTION_ID'] = $parentSectionId;
}

$sectionId = (int)$sectionObject->Add($fields);

if ($sectionId <= 0) {
    $exception = $APPLICATION->GetException();

    die(
        '<span style="color:red;">Ошибка создания раздела: '
        . ($exception ? htmlspecialcharsbx($exception->GetString()) : 'неизвестная ошибка')
        . '</span>'
    );
}

echo '<span style="color:green;">Раздел создан</span><br>';
echo 'ID = ' . $sectionId . '<br>';
echo 'NAME = ' . htmlspecialcharsbx($sectionName) . '<br>';
echo 'CODE = ' . htmlspecialcharsbx($sectionCode) . '<br>';

if ($parentSectionId > 0) {
    echo 'Создан внутри раздела "Товары", ID = ' . $parentSectionId . '<br>';
} else {
    echo '<span style="color:orange;">Раздел создан в корне каталога, потому что раздел "Товары" не найден.</span><br>';
}