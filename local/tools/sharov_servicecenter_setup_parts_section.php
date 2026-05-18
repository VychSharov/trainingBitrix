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

if (!Loader::includeModule('catalog')) {
    die('Модуль catalog не подключен');
}

/*
 * Рабочий каталог запчастей.
 * У тебя запчасти корректно работают в IBLOCK_ID = 15.
 */
$iblockId = 15;

$sectionCode = 'SERVICECENTER_PARTS';
$sectionName = 'Запчасти сервисного центра';

echo '<h2>Настройка раздела запчастей</h2>';

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
    $sectionId = (int)$section['ID'];

    echo 'Раздел уже существует: <b>' . htmlspecialcharsbx($section['NAME']) . '</b><br>';
    echo 'SECTION_ID = ' . $sectionId . '<br>';
} else {
    $sectionObject = new CIBlockSection();

    $sectionId = (int)$sectionObject->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'NAME' => $sectionName,
        'CODE' => $sectionCode,
        'SORT' => 100,
    ]);

    if ($sectionId <= 0) {
        $exception = $APPLICATION->GetException();

        die(
            'Ошибка создания раздела: '
            . ($exception ? htmlspecialcharsbx($exception->GetString()) : 'неизвестная ошибка')
        );
    }

    echo 'Создан раздел: <b>' . htmlspecialcharsbx($sectionName) . '</b><br>';
    echo 'SECTION_ID = ' . $sectionId . '<br>';
}

/*
 * Текущие демонстрационные запчасти.
 * Переносим их в раздел, чтобы они продолжили работать.
 */
$partNames = [
    'Масляный фильтр',
    'Воздушный фильтр',
    'Свеча зажигания',
];

echo '<hr>';
echo '<h3>Привязка текущих запчастей к разделу</h3>';

foreach ($partNames as $partName) {
    $elementResult = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=NAME' => $partName,
            'ACTIVE' => 'Y',
        ],
        false,
        false,
        [
            'ID',
            'NAME',
            'IBLOCK_ID',
        ]
    );

    $found = false;

    while ($element = $elementResult->Fetch()) {
        $found = true;

        $elementId = (int)$element['ID'];

        CIBlockElement::SetElementSection($elementId, [$sectionId]);

        echo 'Товар <b>' . htmlspecialcharsbx($element['NAME']) . '</b> привязан к разделу. ID=' . $elementId . '<br>';
    }

    if (!$found) {
        echo '<span style="color:red;">Не найден товар: ' . htmlspecialcharsbx($partName) . '</span><br>';
    }
}

echo '<hr>';
echo 'Готово.';