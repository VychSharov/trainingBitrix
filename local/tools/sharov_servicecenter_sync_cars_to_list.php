<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER, $DB;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('iblock')) {
    die('Модуль iblock не подключен');
}

$carTable = 'b_sharov_sc_car';
$listIblockId = 19;
$xmlPrefix = 'SC_CAR_';

echo '<h2>Синхронизация автомобилей гаража с общим списком автомобилей</h2>';

$tableCheck = $DB->Query("SHOW TABLES LIKE '{$carTable}'");

if (!$tableCheck->Fetch()) {
    die('Не найдена таблица ' . htmlspecialcharsbx($carTable));
}

function scSyncFindPropertyId(int $iblockId, array $variants): int
{
    foreach ($variants as $variant) {
        $variant = trim((string)$variant);

        if ($variant === '') {
            continue;
        }

        $propertyResult = CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=CODE' => $variant,
            ]
        );

        $property = $propertyResult->Fetch();

        if ($property) {
            return (int)$property['ID'];
        }

        $propertyResult = CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=NAME' => $variant,
            ]
        );

        $property = $propertyResult->Fetch();

        if ($property) {
            return (int)$property['ID'];
        }
    }

    return 0;
}

function scSyncAddPropertyValue(array &$props, int $iblockId, array $variants, $value): void
{
    if ($value === null || $value === '') {
        return;
    }

    $propertyId = scSyncFindPropertyId($iblockId, $variants);

    if ($propertyId > 0) {
        $props[$propertyId] = $value;
    }
}

function scSyncFindListElementId(int $iblockId, string $xmlId): int
{
    $result = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=XML_ID' => $xmlId,
        ],
        false,
        false,
        [
            'ID',
        ]
    );

    $element = $result->Fetch();

    return $element ? (int)$element['ID'] : 0;
}

$result = $DB->Query("
    SELECT *
    FROM {$carTable}
    ORDER BY ID ASC
");

$created = 0;
$updated = 0;
$errors = [];

while ($car = $result->Fetch()) {
    $carId = (int)$car['ID'];
    $contactId = (int)$car['CONTACT_ID'];
    $brand = (string)$car['BRAND'];
    $model = (string)$car['MODEL'];
    $number = (string)$car['LICENSE_PLATE'];
    $year = (int)$car['YEAR'];
    $color = (string)$car['COLOR'];
    $mileage = (int)$car['MILEAGE'];

    $name = trim($brand . ' ' . $model . ' — ' . $number);

    if ($name === '' || $name === '—') {
        $name = 'Автомобиль #' . $carId;
    }

    $props = [];

    scSyncAddPropertyValue($props, $listIblockId, ['BRAND', 'MARKA', 'MARK', 'Марка'], $brand);
    scSyncAddPropertyValue($props, $listIblockId, ['MODEL', 'Модель'], $model);
    scSyncAddPropertyValue($props, $listIblockId, ['LICENSE_PLATE', 'NUMBER', 'CAR_NUMBER', 'STATE_NUMBER', 'Госномер', 'Номер'], $number);
    scSyncAddPropertyValue($props, $listIblockId, ['YEAR', 'CAR_YEAR', 'Год'], $year > 0 ? $year : null);
    scSyncAddPropertyValue($props, $listIblockId, ['COLOR', 'Цвет'], $color);
    scSyncAddPropertyValue($props, $listIblockId, ['MILEAGE', 'PROBEG', 'Пробег'], $mileage > 0 ? $mileage : null);
    scSyncAddPropertyValue($props, $listIblockId, ['CONTACT_ID', 'CLIENT_ID', 'CONTACT', 'Клиент', 'Контакт'], $contactId > 0 ? $contactId : null);
    scSyncAddPropertyValue($props, $listIblockId, ['SC_CAR_ID', 'CAR_ID', 'ID автомобиля'], $carId);

    /*
     * Старые ID свойств списка автомобилей:
     * 73 — год, 74 — цвет, 77 — марка.
     */
    $props[77] = $brand;

    if ($year > 0) {
        $props[73] = $year;
    }

    if ($color !== '') {
        $props[74] = $color;
    }

    $xmlId = $xmlPrefix . $carId;

    $fields = [
        'IBLOCK_ID' => $listIblockId,
        'ACTIVE' => 'Y',
        'NAME' => $name,
        'XML_ID' => $xmlId,
        'PROPERTY_VALUES' => $props,
    ];

    $elementObject = new CIBlockElement();
    $elementId = scSyncFindListElementId($listIblockId, $xmlId);

    if ($elementId > 0) {
        $updateFields = $fields;
        unset($updateFields['IBLOCK_ID']);

        if ($elementObject->Update($elementId, $updateFields)) {
            $updated++;
            echo 'Обновлён: ' . htmlspecialcharsbx($name) . ' → элемент списка ID=' . $elementId . '<br>';
        } else {
            $errors[] = 'Ошибка обновления ' . $name . ': ' . $elementObject->LAST_ERROR;
        }
    } else {
        $newElementId = (int)$elementObject->Add($fields);

        if ($newElementId > 0) {
            $created++;
            echo 'Создан: ' . htmlspecialcharsbx($name) . ' → элемент списка ID=' . $newElementId . '<br>';
        } else {
            $errors[] = 'Ошибка создания ' . $name . ': ' . $elementObject->LAST_ERROR;
        }
    }
}

echo '<hr>';
echo 'Создано: ' . $created . '<br>';
echo 'Обновлено: ' . $updated . '<br>';

if (!empty($errors)) {
    echo '<h3 style="color:red;">Ошибки</h3>';

    foreach ($errors as $error) {
        echo htmlspecialcharsbx($error) . '<br>';
    }
}

echo '<hr>Готово.';