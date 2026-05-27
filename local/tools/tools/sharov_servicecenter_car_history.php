<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

Loader::includeModule('sharov.servicecenter');

$carId = (int)($_REQUEST['carId'] ?? 0);

$APPLICATION->IncludeComponent(
    'sharov:servicecenter.car.history',
    '',
    [
        'CAR_ID' => $carId,
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';