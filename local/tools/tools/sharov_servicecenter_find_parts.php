<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

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
 * 15 — это "Товарный каталог CRM (предложения)".
 * Остатки будем вести по торговым предложениям.
 */
$partsIblockId = 15;

$partNames = [
    'Масляный фильтр',
    'Воздушный фильтр',
    'Свеча зажигания',
];

echo '<h2>Поиск запчастей</h2>';
echo 'Работаем только с IBLOCK_ID = <b>' . (int)$partsIblockId . '</b><br>';

foreach ($partNames as $partName) {
    echo '<hr>';
    echo '<b>Ищем: ' . htmlspecialcharsbx($partName) . '</b><br>';

    $elementResult = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            '=IBLOCK_ID' => $partsIblockId,
            '=NAME' => $partName,
            'ACTIVE' => 'Y',
        ],
        false,
        false,
        [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'ACTIVE',
        ]
    );

    $found = false;

    while ($element = $elementResult->Fetch()) {
        $found = true;

        $productId = (int)$element['ID'];
        $product = CCatalogProduct::GetByID($productId);

        echo 'ID товара/предложения: <b>' . $productId . '</b><br>';
        echo 'IBLOCK_ID: ' . (int)$element['IBLOCK_ID'] . '<br>';
        echo 'Название: ' . htmlspecialcharsbx($element['NAME']) . '<br>';

        if ($product) {
            echo 'Количество в b_catalog_product: <b>' . htmlspecialcharsbx((string)$product['QUANTITY']) . '</b><br>';
            echo 'QUANTITY_TRACE: ' . htmlspecialcharsbx((string)$product['QUANTITY_TRACE']) . '<br>';
            echo 'CAN_BUY_ZERO: ' . htmlspecialcharsbx((string)$product['CAN_BUY_ZERO']) . '<br>';
        } else {
            echo '<span style="color:red;">Нет записи в b_catalog_product</span><br>';
        }

        if (class_exists('CCatalogStoreProduct')) {
            $storeAmountResult = CCatalogStoreProduct::GetList(
                [],
                [
                    'PRODUCT_ID' => $productId,
                ],
                false,
                false,
                [
                    'ID',
                    'STORE_ID',
                    'AMOUNT',
                ]
            );

            while ($storeAmount = $storeAmountResult->Fetch()) {
                echo 'Склад ID ' . (int)$storeAmount['STORE_ID']
                    . ': <b>' . htmlspecialcharsbx((string)$storeAmount['AMOUNT']) . '</b><br>';
            }
        }
    }

    if (!$found) {
        echo '<span style="color:red;">Не найдено в выбранном инфоблоке</span><br>';
    }
}