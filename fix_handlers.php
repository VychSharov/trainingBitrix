<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\EventManager;

$em = EventManager::getInstance();

// удаляем лишний/старый обработчик
$em->unRegisterEventHandler(
    'crm',
    'OnEntityDetailsTabsInitialized',
    'sharov.crmcustomtab',
    'Sharov\\Crmcustomtab\\Crm\\Handlers',
    'onEntityDetailsTabsInitialized'
);

// на всякий случай удалим и вариант с маленькой буквы
$em->unRegisterEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    'sharov.crmcustomtab',
    'Sharov\\Crmcustomtab\\Crm\\Handlers',
    'onEntityDetailsTabsInitialized'
);

echo "OK: removed Handlers::onEntityDetailsTabsInitialized\n";