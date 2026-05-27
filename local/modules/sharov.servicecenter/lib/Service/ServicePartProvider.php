<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;

class ServicePartProvider
{
    private const PRODUCT_IBLOCK_ID = 14;
    private const OFFER_IBLOCK_ID = 15;

    private const PARTS_SECTION_NAME = 'Запчасти сервисного центра';
    private const PARTS_SECTION_CODE = 'SERVICECENTER_PARTS';

    /**
     * Возвращает все запчасти сервисного центра.
     *
     * ВАЖНО:
     * Запчастями считаются только товары из раздела
     * "Запчасти сервисного центра".
     *
     * @return array
     */
    public function getParts(): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не подключен');
        }

        if (!Loader::includeModule('catalog')) {
            throw new \RuntimeException('Модуль catalog не подключен');
        }

        $parts = [];

        /*
         * Основной пользовательский сценарий:
         * пользователь создаёт товар в IBLOCK_ID = 14
         * и выбирает раздел "Запчасти сервисного центра".
         *
         * Для остатков и закупок нам нужен ID торгового предложения из IBLOCK_ID = 15.
         */
        $productIds = $this->getElementsFromPartsSection(self::PRODUCT_IBLOCK_ID);

        foreach ($productIds as $productId) {
            $offerIds = $this->getOfferIdsByProductId($productId);

            if (!empty($offerIds)) {
                foreach ($offerIds as $offerId) {
                    $parts[$offerId] = $this->buildPart($offerId);
                }
            } else {
                /*
                 * На случай, если товар без торгового предложения.
                 */
                $parts[$productId] = $this->buildPart($productId);
            }
        }

        /*
         * Дополнительно поддерживаем старый вариант:
         * если товар/предложение уже лежит прямо в разделе IBLOCK_ID = 15.
         */
        $offerIds = $this->getElementsFromPartsSection(self::OFFER_IBLOCK_ID);

        foreach ($offerIds as $offerId) {
            $parts[$offerId] = $this->buildPart($offerId);
        }

        usort($parts, static function ($a, $b) {
            return strcmp((string)$a['name'], (string)$b['name']);
        });

        return array_values($parts);
    }

    /**
     * Возвращает элементы конкретного инфоблока из раздела "Запчасти сервисного центра".
     *
     * @param int $iblockId
     * @return array
     */
    private function getElementsFromPartsSection(int $iblockId): array
    {
        $sectionId = $this->getPartsSectionId($iblockId);

        if ($sectionId <= 0) {
            return [];
        }

        $result = \CIBlockElement::GetList(
            [
                'NAME' => 'ASC',
            ],
            [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $sectionId,
                'INCLUDE_SUBSECTIONS' => 'Y',
                'ACTIVE' => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'IBLOCK_ID',
            ]
        );

        $ids = [];

        while ($element = $result->Fetch()) {
            $ids[] = (int)$element['ID'];
        }

        return $ids;
    }

    /**
     * Ищет раздел "Запчасти сервисного центра".
     *
     * Сначала ищем по CODE, потом по NAME.
     * Без сложного OR-фильтра, чтобы Битрикс не нашёл не тот раздел.
     *
     * @param int $iblockId
     * @return int
     */
    private function getPartsSectionId(int $iblockId): int
    {
        $resultByCode = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=CODE' => self::PARTS_SECTION_CODE,
            ],
            false,
            [
                'ID',
                'NAME',
                'CODE',
            ]
        );

        $section = $resultByCode->Fetch();

        if ($section) {
            return (int)$section['ID'];
        }

        $resultByName = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=NAME' => self::PARTS_SECTION_NAME,
            ],
            false,
            [
                'ID',
                'NAME',
                'CODE',
            ]
        );

        $section = $resultByName->Fetch();

        return $section ? (int)$section['ID'] : 0;
    }

    /**
     * Возвращает торговые предложения основного товара.
     *
     * @param int $productId
     * @return array
     */
    private function getOfferIdsByProductId(int $productId): array
    {
        if (!class_exists('\CCatalogSKU')) {
            return [];
        }

        $offers = \CCatalogSKU::getOffersList(
            [$productId],
            0,
            [
                'ACTIVE' => 'Y',
            ],
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
            ]
        );

        $ids = [];

        if (!empty($offers[$productId])) {
            foreach ($offers[$productId] as $offer) {
                $ids[] = (int)$offer['ID'];
            }
        }

        return $ids;
    }

    /**
     * Формирует объект запчасти.
     *
     * @param int $productId
     * @return array
     */
    private function buildPart(int $productId): array
    {
        $elementResult = \CIBlockElement::GetList(
            [],
            [
                '=ID' => $productId,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'IBLOCK_ID',
            ]
        );

        $element = $elementResult->Fetch();

        if (!$element) {
            throw new \RuntimeException('Не найден товар ID=' . $productId);
        }

        $product = \CCatalogProduct::GetByID($productId);
        $quantity = $product ? (float)$product['QUANTITY'] : 0;

        return [
            'id' => $productId,
            'label' => $element['NAME'] . ' — остаток: ' . $quantity,
            'name' => $element['NAME'],
            'quantity' => $quantity,
        ];
    }
}