<?php

namespace Sharov\ServiceCenter\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\Logger;

class ContactTabHandler
{
    /**
     * Добавляет вкладку "Гараж" в карточку контакта.
     *
     * @param Event $event
     * @return EventResult
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        $params = $event->getParameters();

        try {
            if (!Loader::includeModule('crm')) {
                return new EventResult(EventResult::SUCCESS, $params);
            }

            $entityTypeId = (int)($params['entityTypeID'] ?? $params['entityTypeId'] ?? 0);
            $entityId = (int)($params['entityID'] ?? $params['entityId'] ?? 0);

            /*
             * В CRM Bitrix контакт обычно имеет entityTypeId = 3.
             * Не используем Bitrix\Crm\OwnerType, потому что в этой версии
             * Битрикса такого класса нет.
             */
            $contactTypeId = 3;

            if ($entityTypeId !== $contactTypeId || $entityId <= 0) {
                return new EventResult(EventResult::SUCCESS, $params);
            }

            if (!isset($params['tabs']) || !is_array($params['tabs'])) {
                $params['tabs'] = [];
            }

            $params['tabs'][] = [
                'id' => 'tab_sharov_servicecenter_garage',
                'name' => 'Гараж',
                'enabled' => true,
                'loader' => [
                    'serviceUrl' => '/local/tools/sharov_servicecenter_garage_lazy.php?'
                        . bitrix_sessid_get()
                        . '&contactId=' . $entityId,
                ],
            ];
        } catch (\Throwable $exception) {
            Logger::error('Garage tab error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return new EventResult(EventResult::SUCCESS, $params);
    }
}