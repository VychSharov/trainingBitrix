<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("ДЗ: Курсы валют");

$APPLICATION->IncludeComponent(
	"otus:currencies", 
	".default", 
	array(
		"CURRENCY" => "BYN",
		"CACHE_TIME" => "0",
		"COMPONENT_TEMPLATE" => ".default",
		"CACHE_TYPE" => "A"
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");