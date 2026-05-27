<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;
use RuntimeException;
use Throwable;

class StockSyncService
{
    /**
     * Запускает синхронизацию остатков запчастей.
     *
     * @return array
     */
    public function sync(): array
    {
        $this->includeModules();

        $result = [];
        $parts = (new ServicePartProvider())->getParts();

        if (empty($parts)) {
            return [
                [
                    'name' => 'Запчасти сервисного центра',
                    'success' => false,
                    'message' => 'Не найдены товары в разделе "Запчасти сервисного центра"',
                ],
            ];
        }

        $stockService = new CatalogStockService();
        $purchaseService = new PurchaseRequestService();

        foreach ($parts as $part) {
            $productId = (int)($part['id'] ?? 0);
            $productName = (string)($part['name'] ?? ('Товар ID ' . $productId));

            try {
                if ($productId <= 0) {
                    throw new RuntimeException('Не указан ID товара');
                }

                $externalQuantity = $stockService->getExternalQuantity();

                if ($externalQuantity > 0) {
                    $stockService->updateQuantity($productId, $externalQuantity);

                    $result[] = [
                        'name' => $productName,
                        'productId' => $productId,
                        'externalQuantity' => $externalQuantity,
                        'realQuantity' => $stockService->getQuantity($productId),
                        'success' => true,
                        'message' => 'Остаток обновлён',
                    ];

                    continue;
                }

                $purchaseId = $purchaseService->createAutoPurchaseRequest($productId, 10);
                $stockService->updateQuantity($productId, 10);

                $result[] = [
                    'name' => $productName,
                    'productId' => $productId,
                    'externalQuantity' => 0,
                    'realQuantity' => $stockService->getQuantity($productId),
                    'purchaseId' => $purchaseId,
                    'success' => true,
                    'message' => 'Остаток был 0. Создана автозакупка 10 шт.',
                ];
            } catch (Throwable $exception) {
                $result[] = [
                    'name' => $productName,
                    'productId' => $productId,
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Подключает нужные модули.
     *
     * @return void
     */
    private function includeModules(): void
    {
        foreach (['iblock', 'catalog', 'crm'] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                throw new RuntimeException('Модуль ' . $moduleId . ' не подключен');
            }
        }

        Loader::includeModule('im');
    }
}
