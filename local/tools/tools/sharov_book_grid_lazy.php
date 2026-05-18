<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;

if (!check_bitrix_sessid()) {
    http_response_code(403);
    die('Bad sessid');
}

if (!Loader::includeModule('sharov.crmcustomtab')) {
    http_response_code(500);
    die('Module not installed');
}

$entityTypeId = (int)($_REQUEST['entityTypeId'] ?? 0);
$entityId = (int)($_REQUEST['entityId'] ?? 0);

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'sharov:book.grid',
    '.default',
    [
        'ENTITY_TYPE_ID' => $entityTypeId,
        'ENTITY_ID' => $entityId,
    ]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');