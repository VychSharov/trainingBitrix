<?php

namespace Sharov\ServiceCenter\Crm;

use Sharov\ServiceCenter\Infrastructure\Logger;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;
use Sharov\ServiceCenter\Service\PurchaseRequestService;

class PurchaseRequestHandler
{
    /**
     * Обрабатывает изменение стадии заявки на закупку.
     *
     * Этот метод можно привязать к событию смарт-процесса в конкретной версии Битрикс24
     * или вызвать из бизнес-процесса/робота через PHP-активити.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param string $stageId
     * @return void
     */
    public static function handleStageChange(int $entityTypeId, int $itemId, string $stageId): void
    {
        try {
            if ($entityTypeId !== ModuleSettings::getPurchaseEntityTypeId()) {
                return;
            }

            $service = new PurchaseRequestService();

            if ($stageId === ModuleSettings::getPurchaseApprovedStageId()) {
                $service->approve($itemId);
                return;
            }

            if ($stageId === ModuleSettings::getPurchaseRejectedStageId()) {
                $service->reject($itemId, 'Заявка отклонена сотрудником отдела закупок');
            }
        } catch (\Throwable $exception) {
            Logger::error('Purchase request stage handler error', [
                'entityTypeId' => $entityTypeId,
                'itemId' => $itemId,
                'stageId' => $stageId,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }
}
