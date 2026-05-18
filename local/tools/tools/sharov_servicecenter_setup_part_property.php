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

$iblockIds = [14, 15];

$propertyCode = 'SC_IS_PART';
$propertyName = 'Запчасть сервисного центра';

echo '<h2>Настройка признака запчасти</h2>';

foreach ($iblockIds as $iblockId) {
    echo '<hr>';
    echo '<h3>IBLOCK_ID = ' . (int)$iblockId . '</h3>';

    $propertyResult = CIBlockProperty::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=CODE' => $propertyCode,
        ]
    );

    $property = $propertyResult->Fetch();

    if ($property) {
        echo 'Свойство уже существует: ID=' . (int)$property['ID'] . '<br>';
        continue;
    }

    $propertyObject = new CIBlockProperty();

    $propertyId = $propertyObject->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'NAME' => $propertyName,
        'CODE' => $propertyCode,
        'PROPERTY_TYPE' => 'L',
        'LIST_TYPE' => 'C',
        'MULTIPLE' => 'N',
        'SORT' => 100,
        'VALUES' => [
            [
                'VALUE' => 'Да',
                'XML_ID' => 'Y',
                'SORT' => 100,
                'DEF' => 'N',
            ],
        ],
    ]);

    if ($propertyId > 0) {
        echo '<span style="color:green;">Создано свойство:</span> '
            . htmlspecialcharsbx($propertyName)
            . ', ID=' . (int)$propertyId
            . '<br>';
    } else {
        $exception = $APPLICATION->GetException();

        echo '<span style="color:red;">Ошибка создания свойства: '
            . ($exception ? htmlspecialcharsbx($exception->GetString()) : 'неизвестная ошибка')
            . '</span><br>';
    }
}

echo '<hr>Готово.';