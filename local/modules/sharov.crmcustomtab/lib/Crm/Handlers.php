<?php

namespace Sharov\Crmcustomtab\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Throwable;

class Handlers
{
    private static function log(string $msg, array $context = []): void
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/sharov_crm_tab.log';
        $date = date('Y-m-d H:i:s');

        $line = '[' . $date . '] ' . $msg;
        if (!empty($context)) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND);
    }

    /**
     * ВАЖНО!!!
     * У тебя событие уже зарегистрировано на метод updateTabs (по логу).
     * Поэтому делаем алиас: updateTabs -> onEntityDetailsTabsInitialized.
     *
     * @param Event $event
     * @return EventResult
     */
    public static function updateTabs(Event $event): EventResult
    {
        return self::onEntityDetailsTabsInitialized($event);
    }

    /**
     * Основная логика добавления вкладки.
     *
     * @param Event $event
     * @return EventResult
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        try {
            $params = $event->getParameters();

            $entityTypeId = (int)($params['entityTypeID'] ?? $params['entityTypeId'] ?? 0);
            $entityId     = (int)($params['entityID'] ?? $params['entityId'] ?? 0);

            self::log('START', [
                'entityTypeId' => $entityTypeId,
                'entityId' => $entityId,
                'keys' => array_keys($params),
            ]);

            if ($entityTypeId <= 0 || $entityId <= 0) {
                self::log('EXIT: bad entity ids');
                return new EventResult(EventResult::SUCCESS, $params);
            }

            if (!isset($params['tabs']) || !is_array($params['tabs'])) {
                self::log('EXIT: tabs missing', [
                    'tabs_exists' => isset($params['tabs']),
                    'tabs_type' => isset($params['tabs']) ? gettype($params['tabs']) : 'null',
                ]);
                return new EventResult(EventResult::SUCCESS, $params);
            }

            $serviceUrl =
                '/local/tools/sharov_book_grid_lazy.php?' . bitrix_sessid_get()
                . '&entityTypeId=' . $entityTypeId
                . '&entityId=' . $entityId;

            $params['tabs'][] = [
                'id' => 'tab_sharov_books',
                'name' => Loc::getMessage('SHAROV_TAB_BOOKS') ?: 'Книги',
                'enabled' => true,
                'loader' => [
                    'serviceUrl' => $serviceUrl,
                ],
            ];

            self::log('OK: tab added', [
                'serviceUrl' => $serviceUrl,
                'tabs_count' => count($params['tabs']),
            ]);

            return new EventResult(EventResult::SUCCESS, $params);
        } catch (Throwable $e) {
            self::log('ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // главное: не роняем CRM
            return new EventResult(EventResult::SUCCESS, $event->getParameters());
        }
    }
}