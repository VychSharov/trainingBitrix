<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\EventManager;

echo "<pre>";

$em = EventManager::getInstance();

// пробуем оба варианта имени события (на всякий случай)
$handlers1 = $em->findEventHandlers('crm', 'OnEntityDetailsTabsInitialized');
$handlers2 = $em->findEventHandlers('crm', 'onEntityDetailsTabsInitialized');

echo "Handlers for crm:OnEntityDetailsTabsInitialized\n";
print_r($handlers1);

echo "\nHandlers for crm:onEntityDetailsTabsInitialized\n";
print_r($handlers2);

echo "</pre>";