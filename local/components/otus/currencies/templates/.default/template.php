<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$data = isset($arResult["CURRENCY_DATA"]) ? $arResult["CURRENCY_DATA"] : null;
$code = (string)($arResult["CURRENCY"] ?? "");

if (!$data)
{
    echo htmlspecialcharsbx($code) . " = ?";
    return;
}

$rate = $data["AMOUNT"];        // курс по умолчанию
echo htmlspecialcharsbx($code) . " = " . htmlspecialcharsbx($rate);