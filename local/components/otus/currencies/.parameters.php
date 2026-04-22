<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arCurrentValues */

require __DIR__ . "/lang/ru/rulang.php";

if (!CModule::IncludeModule("currency"))
{
    return;
}

$currencyValues = array();

$by = "SORT";
$order = "ASC";
$rs = CCurrency::GetList($by, $order);
while ($item = $rs->Fetch())
{
    $code = $item["CURRENCY"];
    $name = trim((string)$item["FULL_NAME"]);
    $currencyValues[$code] = ($name !== "") ? ($code . " - " . $name) : $code;
}

$arComponentParameters = array(
    "GROUPS" => array(
        "MAIN" => array(
            "NAME" => GetMessage("OTUS_CURRENCIES_GROUP_MAIN"),
            "SORT" => 100,
        ),
    ),
    "PARAMETERS" => array(
        "CURRENCY" => array(
            "PARENT" => "MAIN",
            "NAME" => GetMessage("OTUS_CURRENCIES_PARAM_CURRENCY"),
            "TYPE" => "LIST",
            "VALUES" => $currencyValues,
            "DEFAULT" => "USD",
            "REFRESH" => "N",
        ),
        "CACHE_TIME" => array(
            "DEFAULT" => 3600,
        ),
    ),
);