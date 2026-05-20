<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('sharov.servicecenter', [
    'Sharov\ServiceCenter\Model\CarTable' => 'lib/Model/CarTable.php',

    'Sharov\ServiceCenter\Infrastructure\Logger' => 'lib/Infrastructure/Logger.php',
    'Sharov\ServiceCenter\Infrastructure\ModuleSettings' => 'lib/Infrastructure/ModuleSettings.php',

    'Sharov\ServiceCenter\Crm\ContactTabHandler' => 'lib/Crm/ContactTabHandler.php',
    'Sharov\ServiceCenter\Crm\DealEventHandler' => 'lib/Crm/DealEventHandler.php',
    'Sharov\ServiceCenter\Crm\PurchaseRequestHandler' => 'lib/Crm/PurchaseRequestHandler.php',

    'Sharov\ServiceCenter\Service\CarService' => 'lib/Service/CarService.php',
    'Sharov\ServiceCenter\Service\DealService' => 'lib/Service/DealService.php',
    'Sharov\ServiceCenter\Service\CrmAutoResolver' => 'lib/Service/CrmAutoResolver.php',
    'Sharov\ServiceCenter\Service\CarHistoryService' => 'lib/Service/CarHistoryService.php',
    'Sharov\ServiceCenter\Service\CatalogStockService' => 'lib/Service/CatalogStockService.php',
    'Sharov\ServiceCenter\Service\StockSyncService' => 'lib/Service/StockSyncService.php',
    'Sharov\ServiceCenter\Service\ServicePartProvider' => 'lib/Service/ServicePartProvider.php',
    'Sharov\ServiceCenter\Service\PurchaseRequestService' => 'lib/Service/PurchaseRequestService.php',
    'Sharov\ServiceCenter\Service\NotificationService' => 'lib/Service/NotificationService.php',
    'Sharov\ServiceCenter\Service\ApproverResolver' => 'lib/Service/ApproverResolver.php',
    'Sharov\ServiceCenter\Service\SetupValidator' => 'lib/Service/SetupValidator.php',

    'Sharov\ServiceCenter\Agent\StockSyncAgent' => 'lib/Agent/StockSyncAgent.php',
    'Sharov\ServiceCenter\Agent\PurchaseRequestAgent' => 'lib/Agent/PurchaseRequestAgent.php',
]);