<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;

class CatalogStockService
{
    /**
     * Получает внешний остаток из тестового сервиса.
     *
     * @return int
     */
    public function getExternalQuantity(): int
    {
        $url = ModuleSettings::getExternalQuantityUrl();
        if ($url === '') {
            throw new \RuntimeException('Не задан URL внешнего сервиса остатков');
        }

        $response = @file_get_contents($url);

        if ($response === false) {
            throw new \RuntimeException('Не удалось получить остаток из внешнего сервиса');
        }

        $quantity = (int)trim($response);

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

        $product = \Bitrix\Catalog\ProductTable::getByPrimary($productId)->fetch();

        return $product ? (float)$product['QUANTITY'] : 0.0;
    }

    /**
     * Обновляет количество товара.
     *
     * @param int $productId
     * @param float $quantity
     * @return void
     */
    public function updateQuantity(int $productId, float $quantity): void
    {
        Loader::includeModule('catalog');

        $result = \Bitrix\Catalog\ProductTable::update($productId, [
            'QUANTITY' => $quantity,
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
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
     * @return array
     */
    public function getTrackedProductIds(): array
    {
        return ModuleSettings::getTrackedProductIds();
    }
}
