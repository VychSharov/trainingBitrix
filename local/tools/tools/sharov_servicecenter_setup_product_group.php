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
$propertyName = 'Группа товаров';
$enumValue = 'Запчасти сервисного центра';
$enumXmlId = 'SERVICECENTER_PARTS';

echo '<h2>Настройка группы товаров для запчастей</h2>';

foreach ($iblockIds as $iblockId) {
    echo '<hr>';
    echo '<h3>IBLOCK_ID = ' . (int)$iblockId . '</h3>';

    $propertyResult = CIBlockProperty::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'NAME' => $propertyName,
        ]
    );

    $property = $propertyResult->Fetch();

    if (!$property) {
        echo '<span style="color:red;">Свойство "' . htmlspecialcharsbx($propertyName) . '" не найдено</span><br>';
        continue;
    }

    echo 'Найдено свойство: ID=' . (int)$property['ID']
        . ', CODE=' . htmlspecialcharsbx((string)$property['CODE'])
        . ', TYPE=' . htmlspecialcharsbx((string)$property['PROPERTY_TYPE'])
        . '<br>';

    if ($property['PROPERTY_TYPE'] !== 'L') {
        echo '<span style="color:orange;">Свойство не списочное. Значение надо будет вводить руками: '
            . htmlspecialcharsbx($enumValue)
            . '</span><br>';
        continue;
    }

    $enumResult = CIBlockPropertyEnum::GetList(
        [],
        [
            'PROPERTY_ID' => (int)$property['ID'],
            'VALUE' => $enumValue,
        ]
    );

    $enum = $enumResult->Fetch();

    if ($enum) {
        echo 'Значение уже существует: '
            . htmlspecialcharsbx($enum['VALUE'])
            . ', ENUM_ID=' . (int)$enum['ID']
            . '<br>';
        continue;
    }

    $enumObject = new CIBlockPropertyEnum();

    $enumId = $enumObject->Add([
        'PROPERTY_ID' => (int)$property['ID'],
        'VALUE' => $enumValue,
        'XML_ID' => $enumXmlId,
        'SORT' => 100,
    ]);

    if ($enumId > 0) {
        echo '<span style="color:green;">Добавлено значение:</span> '
            . htmlspecialcharsbx($enumValue)
            . ', ENUM_ID=' . (int)$enumId
            . '<br>';
    } else {
        echo '<span style="color:red;">Не удалось добавить значение</span><br>';
    }
}

echo '<hr>Готово.';