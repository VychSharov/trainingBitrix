<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require __DIR__ . "/lang/ru/rulang.php";

$arComponentDescription = array(
    "NAME" => GetMessage("OTUS_CURRENCIES_NAME"),
    "DESCRIPTION" => GetMessage("OTUS_CURRENCIES_DESC"),
    "SORT" => 20,
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "otus",
        "CHILD" => array(
            "ID" => "currencies",
            "NAME" => GetMessage("OTUS_CURRENCIES_GROUP"),
            "SORT" => 10,
        ),
    ),
);