<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

use Bitrix\Main\Loader;

Loader::includeModule('sharov.servicecenter');

$APPLICATION->IncludeComponent(
    'sharov:servicecenter.garage',
    '',
    [
        'CONTACT_ID' => 9,
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';