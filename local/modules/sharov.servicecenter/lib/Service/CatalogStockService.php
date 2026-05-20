<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;

class CatalogStockService
{
    /**
     * Получает внешний остаток из настроенного тестового сервиса.
     *
     * @return int
     */
    public function getExternalQuantity(): int
    {
        $url = ModuleSettings::getExternalQuantityUrl();

        if ($url === '') {
            throw new \RuntimeException('Не задан URL внешнего сервиса остатков');
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            /*
             * Для демонстрационного проекта оставляем fallback,
             * чтобы агент не падал при недоступности random.org.
             */
            return random_int(0, 10);
        }

        $quantity = (int)trim((string)$response);

        if ($quantity < 0) {
            throw new \RuntimeException('Внешний сервис вернул некорректный остаток');
        }

        return $quantity;
    }

    /**
     * Возвращает текущий остаток товара из каталога.
     *
     * @param int $productId
     * @return float
     */
    public function getQuantity(int $productId): float
    {
        Loader::includeModule('catalog');

        if (class_exists('\CCatalogProduct')) {
            $product = \CCatalogProduct::GetByID($productId);

            return $product ? (float)$product['QUANTITY'] : 0.0;
        }

        $product = \Bitrix\Catalog\ProductTable::getByPrimary($productId)->fetch();

        return $product ? (float)$product['QUANTITY'] : 0.0;
    }

    /**
     * Обновляет количество товара в b_catalog_product и на первом активном складе.
     *
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    public function updateQuantity(int $productId, float $quantity): void
    {
        Loader::includeModule('catalog');

        if (class_exists('\CCatalogProduct')) {
            $product = \CCatalogProduct::GetByID($productId);

            if ($product) {
                $result = \CCatalogProduct::Update($productId, [
                    'QUANTITY' => $quantity,
                    'QUANTITY_TRACE' => 'N',
                    'CAN_BUY_ZERO' => 'Y',
                ]);
            } else {
                $result = \CCatalogProduct::Add([
                    'ID' => $productId,
                    'QUANTITY' => $quantity,
                    'QUANTITY_TRACE' => 'N',
                    'CAN_BUY_ZERO' => 'Y',
                ]);
            }

            if (!$result) {
                global $APPLICATION;
                $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;

                throw new \RuntimeException(
                    'Не удалось обновить остаток товара ID=' . $productId . '. '
                    . ($exception ? $exception->GetString() : 'Ошибка неизвестна')
                );
            }
        } else {
            $result = \Bitrix\Catalog\ProductTable::update($productId, [
                'QUANTITY' => $quantity,
                'QUANTITY_TRACE' => 'N',
                'CAN_BUY_ZERO' => 'Y',
            ]);

            if (!$result->isSuccess()) {
                throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
            }
        }

        $this->updateFirstStoreAmount($productId, $quantity);
    }

    /**
     * Увеличивает остаток товара.
     *
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    public function increaseQuantity(int $productId, float $quantity): void
    {
        $currentQuantity = $this->getQuantity($productId);

        $this->updateQuantity($productId, $currentQuantity + $quantity);
    }

    /**
     * Возвращает список запчастей для синхронизации.
     *
     * Сначала берём товары из раздела "Запчасти сервисного центра".
     * Если раздел не настроен, используем старую настройку tracked_product_ids.
     *
     * @return array
     */
    public function getTrackedProductIds(): array
    {
        try {
            $parts = (new ServicePartProvider())->getParts();
            $productIds = [];

            foreach ($parts as $part) {
                $productId = (int)($part['id'] ?? 0);

                if ($productId > 0) {
                    $productIds[] = $productId;
                }
            }

            $productIds = array_values(array_unique($productIds));

            if (!empty($productIds)) {
                return $productIds;
            }
        } catch (\Throwable $exception) {
            /*
             * Если раздел запчастей ещё не настроен, падаем обратно на настройки модуля.
             */
        }

        return ModuleSettings::getTrackedProductIds();
    }

    /**
     * Обновляет остаток на первом активном складе.
     *
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    private function updateFirstStoreAmount(int $productId, float $quantity): void
    {
        if (!class_exists('\CCatalogStore') || !class_exists('\CCatalogStoreProduct')) {
            return;
        }

        $storeResult = \CCatalogStore::GetList(
            ['ID' => 'ASC'],
            ['ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'TITLE']
        );

        $store = $storeResult->Fetch();

        if (!$store) {
            return;
        }

        $storeId = (int)$store['ID'];

        $storeAmountResult = \CCatalogStoreProduct::GetList(
            [],
            [
                'PRODUCT_ID' => $productId,
                'STORE_ID' => $storeId,
            ],
            false,
            false,
            [
                'ID',
                'AMOUNT',
            ]
        );

        $storeAmount = $storeAmountResult->Fetch();

        if ($storeAmount) {
            \CCatalogStoreProduct::Update(
                (int)$storeAmount['ID'],
                [
                    'AMOUNT' => $quantity,
                ]
            );

            return;
        }

        \CCatalogStoreProduct::Add([
            'PRODUCT_ID' => $productId,
            'STORE_ID' => $storeId,
            'AMOUNT' => $quantity,
        ]);
    }
}
