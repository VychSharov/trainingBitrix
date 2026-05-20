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

            $approvedStageId = ModuleSettings::getPurchaseApprovedStageId();
            $doneStageId = ModuleSettings::getPurchaseDoneStageId();

            if ($stageId === $approvedStageId || $stageId === $doneStageId) {
                $service->approve($itemId);
                return;
            }

            if ($stageId === ModuleSettings::getPurchaseRejectedStageId()) {
                $service->reject($itemId, '');
            }
        } catch (\Throwable $exception) {
            Logger::error('Purchase request stage handler error', [
                'entityTypeId' => $entityTypeId,
                'itemId' => $itemId,
                'stageId' => $stageId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
