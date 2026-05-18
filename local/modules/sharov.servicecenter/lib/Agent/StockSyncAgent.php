<?php

namespace Sharov\ServiceCenter\Agent;

use Sharov\ServiceCenter\Infrastructure\Logger;
use Sharov\ServiceCenter\Service\CatalogStockService;
use Sharov\ServiceCenter\Service\PurchaseRequestService;

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
            $stockService = new CatalogStockService();
            $purchaseService = new PurchaseRequestService();
            $productIds = $stockService->getTrackedProductIds();

            if (empty($productIds)) {
                Logger::info('Stock sync skipped: tracked products are not configured');

                return '\\Sharov\\ServiceCenter\\Agent\\StockSyncAgent::run();';
            }

            foreach ($productIds as $productId) {
                $quantity = $stockService->getExternalQuantity();

                if ($quantity === 0) {
                    $purchaseService->createAutoPurchaseRequest($productId, 10);
                    $stockService->updateQuantity($productId, 10);
                    continue;
                }

                $stockService->updateQuantity($productId, $quantity);
            }
        } catch (\Throwable $exception) {
            Logger::error('Stock sync error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return '\Sharov\ServiceCenter\Agent\StockSyncAgent::run();';
    }
}