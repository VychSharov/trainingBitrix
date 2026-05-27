<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

if (!Loader::includeModule('sharov.servicecenter')) {
    die('Модуль sharov.servicecenter не подключен');
}

$agentName = '\\Sharov\\ServiceCenter\\Agent\\PurchaseRequestAgent::run();';

$agentResult = CAgent::GetList(
    [],
    [
        'MODULE_ID' => 'sharov.servicecenter',
        'NAME' => $agentName,
    ]
);

$existingAgent = $agentResult->Fetch();

if ($existingAgent) {
    echo 'Агент уже зарегистрирован. ID=' . (int)$existingAgent['ID'];
    die();
}

$agentId = CAgent::AddAgent(
    $agentName,
    'sharov.servicecenter',
    'N',
    60,
    '',
    'Y',
    ConvertTimeStamp(time() + 60, 'FULL')
);

if ($agentId) {
    echo 'Агент обработки заявок зарегистрирован. ID=' . (int)$agentId;
} else {
    echo 'Не удалось зарегистрировать агент';
}