<?php

namespace Sharov\ServiceCenter\Agent;

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\Logger;
use Sharov\ServiceCenter\Service\StockSyncService;
use Throwable;

class StockSyncAgent
{
    /**
     * Ежедневно синхронизирует остатки запчастей.
     *
     * @return string
     */
    public static function run(): string
    {
        try {
            if (!Loader::includeModule('sharov.servicecenter')) {
                throw new \RuntimeException('Модуль sharov.servicecenter не подключен');
            }

            $result = (new StockSyncService())->sync();

            Logger::info('Stock sync agent finished', [
                'result' => $result,
            ]);
        } catch (Throwable $exception) {
            Logger::error('Stock sync agent error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return '\\Sharov\\ServiceCenter\\Agent\\StockSyncAgent::run();';
    }
}
