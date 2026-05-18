<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER, $DB;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

$DB->Query("
    CREATE TABLE IF NOT EXISTS sharov_sc_purchase_processed (
        ID INT NOT NULL AUTO_INCREMENT,
        ENTITY_TYPE_ID INT NOT NULL,
        ITEM_ID INT NOT NULL,
        STATUS VARCHAR(50) NOT NULL,
        DATE_CREATE DATETIME NOT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY UX_SHAROV_SC_PURCHASE_PROCESSED_ITEM (ENTITY_TYPE_ID, ITEM_ID)
    )
");

echo 'Таблица sharov_sc_purchase_processed создана или уже существует';