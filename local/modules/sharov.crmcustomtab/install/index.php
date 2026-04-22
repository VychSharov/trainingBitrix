<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Class sharov_crmcustomtab
 *
 * Модуль добавляет вкладку в CRM и выводит данные таблицы sharov_crm_books в стандартном UI Grid.
 */
class sharov_crmcustomtab extends CModule
{
    public $MODULE_ID = 'sharov.crmcustomtab';

    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    /**
     * sharov_crmcustomtab constructor.
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? date('Y-m-d');

        $this->MODULE_NAME = Loc::getMessage('SHAROV_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SHAROV_MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('SHAROV_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('SHAROV_PARTNER_URI');
    }

    /**
     * Установка модуля.
     *
     * @return void
     */
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->doInstallFiles();
        $this->doInstallEvents();
    }

    /**
     * Удаление модуля.
     *
     * @return void
     */
    public function DoUninstall()
    {
        $this->doUninstallEvents();
        $this->doUninstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Копирование компонентов и tools.
     *
     * @return void
     */
    private function doInstallFiles(): void
    {
        // 1) Компонент из install/components -> /local/components
        $fromComponent = __DIR__ . '/components/sharov/book.grid';
        $toComponent = $_SERVER['DOCUMENT_ROOT'] . '/local/components/sharov/book.grid';

        // на всякий случай удалим старую версию
        if (Directory::isDirectoryExists($toComponent)) {
            Directory::deleteDirectory($toComponent);
        }

        CopyDirFiles(
            $fromComponent,
            $toComponent,
            true,  // rewrite
            true   // recursive
        );

        // 2) tools-скрипт из install/tools -> /bitrix/tools
        $fromToolFile = __DIR__ . '/tools/sharov_book_grid_lazy.php';
        $toToolFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/sharov_book_grid_lazy.php';

        CopyDirFiles(
            $fromToolFile,
            $toToolFile,
            true,
            true
        );
    }

    /**
     * Удаление компонентов и tools.
     *
     * @return void
     */
    private function doUninstallFiles(): void
    {
        $componentPath = $_SERVER['DOCUMENT_ROOT'] . '/local/components/sharov/book.grid';
        if (Directory::isDirectoryExists($componentPath)) {
            Directory::deleteDirectory($componentPath);
        }

        $toolFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/sharov_book_grid_lazy.php';
        if (file_exists($toolFile)) {
            @unlink($toolFile);
        }
    }

    /**
     * Регистрация событий.
     *
     * @return void
     */
    private function doInstallEvents(): void
    {
        EventManager::getInstance()->registerEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\Sharov\Crmcustomtab\Crm\Handlers',
            'onEntityDetailsTabsInitialized'
        );
    }

    /**
     * Снятие событий.
     *
     * @return void
     */
    private function doUninstallEvents(): void
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\Sharov\Crmcustomtab\Crm\Handlers',
            'onEntityDetailsTabsInitialized'
        );
    }
}