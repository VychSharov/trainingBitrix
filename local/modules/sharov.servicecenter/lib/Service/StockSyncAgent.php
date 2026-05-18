<?php

namespace Sharov\ServiceCenter\Agent;

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Service\StockSyncService;
use Throwable;

class StockSyncAgent
{
    /**
     * Агент ежедневной синхронизации остатков запчастей.
     *
     * @return string
     */
    public static function run(): string
    {
        try {
            Loader::includeModule('sharov.servicecenter');

            $service = new StockSyncService();
            $service->sync();
        } catch (Throwable $exception) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/local/logs/servicecenter_stock_agent.log',
                '[' . date('Y-m-d H:i:s') . '] '
                . $exception->getMessage()
                . ' in ' . $exception->getFile()
                . ':' . $exception->getLine()
                . PHP_EOL,
                FILE_APPEND
            );
        }

        return '\\Sharov\\ServiceCenter\\Agent\\StockSyncAgent::run();';
    }
}