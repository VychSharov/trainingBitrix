<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class sharov_servicecenter extends CModule
{
    public $MODULE_ID = 'sharov.servicecenter';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('SHAROV_SC_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SHAROV_SC_MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('SHAROV_SC_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('SHAROV_SC_PARTNER_URI');
    }

    /**
     * Установка модуля.
     *
     * @return void
     */
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->createModuleTables();
        $this->copyModuleFiles();
        $this->registerModuleEvents();
        $this->registerModuleAgents();
        $this->createModuleGroups();
    }

    /**
     * Удаление модуля.
     *
     * @return void
     */
    public function DoUninstall()
    {
        $this->removeModuleAgents();
        $this->unregisterModuleEvents();
        $this->removeModuleFiles();
        //$this->dropModuleTables();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Создаёт таблицы модуля.
     *
     * @return void
     */
    private function createModuleTables()
    {
        global $DB;

        $sqlFile = __DIR__ . '/db/mysql/install.sql';

        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);

        foreach ($DB->ParseSQLBatch($sql) as $query) {
            $DB->Query($query);
        }
    }

    /**
     * Удаляет таблицы модуля.
     *
     * @return void
     */
    private function dropModuleTables()
    {
        global $DB;

        $sqlFile = __DIR__ . '/db/mysql/uninstall.sql';

        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);

        foreach ($DB->ParseSQLBatch($sql) as $query) {
            $DB->Query($query);
        }
    }

    /**
     * Копирует компоненты и tool-файлы.
     *
     * @return void
     */
    private function copyModuleFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );

        CopyDirFiles(
            __DIR__ . '/tools',
            $_SERVER['DOCUMENT_ROOT'] . '/local/tools',
            true,
            true
        );
    }

    /**
     * Удаляет скопированные файлы.
     *
     * @return void
     */
    private function removeModuleFiles()
    {
        $garageComponent = $_SERVER['DOCUMENT_ROOT'] . '/local/components/sharov/servicecenter.garage';
        $historyComponent = $_SERVER['DOCUMENT_ROOT'] . '/local/components/sharov/servicecenter.car.history';

        if (Directory::isDirectoryExists($garageComponent)) {
            Directory::deleteDirectory($garageComponent);
        }

        if (Directory::isDirectoryExists($historyComponent)) {
            Directory::deleteDirectory($historyComponent);
        }

        @unlink($_SERVER['DOCUMENT_ROOT'] . '/local/tools/sharov_servicecenter_garage_lazy.php');
        @unlink($_SERVER['DOCUMENT_ROOT'] . '/local/tools/sharov_servicecenter_car_history.php');
    }

    /**
     * Регистрирует обработчики событий.
     *
     * @return void
     */
    private function registerModuleEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\ContactTabHandler',
            'onEntityDetailsTabsInitialized'
        );

        $eventManager->registerEventHandler(
            'crm',
            'OnBeforeCrmDealAdd',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\DealEventHandler',
            'onBeforeDealAdd'
        );

        $eventManager->registerEventHandler(
            'crm',
            'OnBeforeCrmDealUpdate',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\DealEventHandler',
            'onBeforeDealUpdate'
        );
    }

    /**
     * Удаляет обработчики событий.
     *
     * @return void
     */
    private function unregisterModuleEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\ContactTabHandler',
            'onEntityDetailsTabsInitialized'
        );

        $eventManager->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmDealAdd',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\DealEventHandler',
            'onBeforeDealAdd'
        );

        $eventManager->unRegisterEventHandler(
            'crm',
            'OnBeforeCrmDealUpdate',
            $this->MODULE_ID,
            '\Sharov\ServiceCenter\Crm\DealEventHandler',
            'onBeforeDealUpdate'
        );
    }

    /**
     * Регистрирует агента синхронизации остатков.
     *
     * @return void
     */
    private function registerModuleAgents()
    {
        CAgent::AddAgent(
            '\Sharov\ServiceCenter\Agent\StockSyncAgent::run();',
            $this->MODULE_ID,
            'N',
            86400
        );
    }

    /**
     * Удаляет агентов модуля.
     *
     * @return void
     */
    private function removeModuleAgents()
    {
        CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    /**
     * Создаёт группы пользователей для закупок.
     *
     * @return void
     */
    private function createModuleGroups()
    {
        global $APPLICATION;

        $this->createGroupIfNotExists(
            'SERVICECENTER_PURCHASERS',
            'Закупщики сервисного центра'
        );

        $this->createGroupIfNotExists(
            'SERVICECENTER_PURCHASE_HEAD',
            'Начальники закупок сервисного центра'
        );
    }

    /**
     * Создаёт группу, если она ещё не существует.
     *
     * @param string $stringId
     * @param string $name
     * @return void
     */
    private function createGroupIfNotExists($stringId, $name)
    {
        $groupResult = CGroup::GetList(
            $by = 'id',
            $order = 'asc',
            [
                'STRING_ID' => $stringId,
            ]
        );

        if ($groupResult->Fetch()) {
            return;
        }

        $group = new CGroup();

        $group->Add([
            'ACTIVE' => 'Y',
            'C_SORT' => 100,
            'NAME' => $name,
            'STRING_ID' => $stringId,
        ]);
    }
}