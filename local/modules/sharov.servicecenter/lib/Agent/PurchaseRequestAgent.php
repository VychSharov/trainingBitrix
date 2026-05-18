<?php

namespace Sharov\ServiceCenter\Agent;

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Service\PurchaseRequestService;
use Throwable;

class PurchaseRequestAgent
{
    /**
     * Агент обработки ручных заявок на закупку.
     *
     * @return string
     */
    public static function run(): string
    {
        try {
            if (!Loader::includeModule('sharov.servicecenter')) {
                throw new \RuntimeException('Модуль sharov.servicecenter не подключен');
            }

            $service = new PurchaseRequestService();
            $result = $service->processPending();

            if (!empty($result)) {
                self::writeLog($result);
            }
        } catch (Throwable $exception) {
            self::writeLog([
                [
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ]);
        }

        return '\\Sharov\\ServiceCenter\\Agent\\PurchaseRequestAgent::run();';
    }

    /**
     * Пишет лог обработки заявок.
     *
     * @param array $data
     * @return void
     */
    private static function writeLog(array $data): void
    {
        $logDir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . '/servicecenter_purchase_agent.log',
            '[' . date('Y-m-d H:i:s') . '] ' . print_r($data, true) . PHP_EOL,
            FILE_APPEND
        );
    }
}