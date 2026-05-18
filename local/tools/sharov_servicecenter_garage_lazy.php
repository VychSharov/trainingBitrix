<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

if (!check_bitrix_sessid()) {
    die('Bad sessid');
}

Loader::includeModule('sharov.servicecenter');

$contactId = (int)($_REQUEST['contactId'] ?? 0);

$APPLICATION->IncludeComponent(
    'sharov:servicecenter.garage',
    '',
    [
        'CONTACT_ID' => $contactId,
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';