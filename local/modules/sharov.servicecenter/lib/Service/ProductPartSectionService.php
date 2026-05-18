<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;

class ProductPartSectionService
{
    private const SECTION_CODE = 'SERVICECENTER_PARTS';
    private const SECTION_NAME = 'Запчасти сервисного центра';

    public function isPart(int $productId): bool
    {
        $elements = $this->getRelatedElements($productId);

        foreach ($elements as $element) {
            $sectionId = $this->getPartSectionId((int)$element['IBLOCK_ID'], false);

            if ($sectionId <= 0) {
                continue;
            }

            if ($this->isElementInSection((int)$element['ID'], $sectionId)) {
                return true;
            }
        }

        return false;
    }

    public function setPart(int $productId, bool $isPart): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не подключен');
        }

        if (!Loader::includeModule('catalog')) {
            throw new \RuntimeException('Модуль catalog не подключен');
        }

        $elements = $this->getRelatedElements($productId);

        if (empty($elements)) {
            throw new \RuntimeException('Товар не найден');
        }

        foreach ($elements as $element) {
            $this->setElementPartSection(
                (int)$element['ID'],
                (int)$element['IBLOCK_ID'],
                $isPart
            );
        }
    }

    private function getRelatedElements(int $productId): array
    {
        $element = $this->getElement($productId);

        if (!$element) {
            return [];
        }

        $elements = [
            (int)$element['ID'] => $element,
        ];

        foreach ($this->getOfferIdsByProductId((int)$element['ID']) as $offerId) {
            $offer = $this->getElement($offerId);

            if ($offer) {
                $elements[(int)$offer['ID']] = $offer;
            }
        }

        $parentId = $this->getParentProductIdByOfferId(
            (int)$element['ID'],
            (int)$element['IBLOCK_ID']
        );

        if ($parentId > 0) {
            $parent = $this->getElement($parentId);

            if ($parent) {
                $elements[(int)$parent['ID']] = $parent;
            }

            foreach ($this->getOfferIdsByProductId($parentId) as $offerId) {
                $offer = $this->getElement($offerId);

                if ($offer) {
                    $elements[(int)$offer['ID']] = $offer;
                }
            }
        }

        return array_values($elements);
    }

    private function getElement(int $productId): ?array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Модуль iblock не подключен');
        }

        $result = \CIBlockElement::GetList(
            [],
            [
                '=ID' => $productId,
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
            ]
        );

        $element = $result->Fetch();

        return $element ?: null;
    }

    private function setElementPartSection(int $elementId, int $iblockId, bool $isPart): void
    {
        $sectionId = $this->getPartSectionId($iblockId, true);

        if ($sectionId <= 0) {
            throw new \RuntimeException('Не найден раздел "' . self::SECTION_NAME . '"');
        }

        $currentSections = [];

        $groupsResult = \CIBlockElement::GetElementGroups(
            $elementId,
            true,
            [
                'ID',
            ]
        );

        while ($group = $groupsResult->Fetch()) {
            $currentSections[] = (int)$group['ID'];
        }

        if ($isPart) {
            $currentSections[] = $sectionId;
        } else {
            $currentSections = array_filter(
                $currentSections,
                static function ($currentSectionId) use ($sectionId) {
                    return (int)$currentSectionId !== (int)$sectionId;
                }
            );
        }

        $currentSections = array_values(array_unique(array_map('intval', $currentSections)));

        \CIBlockElement::SetElementSection($elementId, $currentSections);
    }

    private function isElementInSection(int $elementId, int $sectionId): bool
    {
        $groupsResult = \CIBlockElement::GetElementGroups(
            $elementId,
            true,
            [
                'ID',
            ]
        );

        while ($group = $groupsResult->Fetch()) {
            if ((int)$group['ID'] === $sectionId) {
                return true;
            }
        }

        return false;
    }

    private function getPartSectionId(int $iblockId, bool $createIfMissing): int
    {
        $sectionResult = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=CODE' => self::SECTION_CODE,
            ],
            false,
            [
                'ID',
                'NAME',
                'CODE',
            ]
        );

        $section = $sectionResult->Fetch();

        if ($section) {
            return (int)$section['ID'];
        }

        $sectionResult = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=NAME' => self::SECTION_NAME,
            ],
            false,
            [
                'ID',
                'NAME',
                'CODE',
            ]
        );

        $section = $sectionResult->Fetch();

        if ($section) {
            return (int)$section['ID'];
        }

        if (!$createIfMissing) {
            return 0;
        }

        $sectionObject = new \CIBlockSection();

        $sectionId = (int)$sectionObject->Add([
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'NAME' => self::SECTION_NAME,
            'CODE' => self::SECTION_CODE,
            'SORT' => 100,
        ]);

        return $sectionId;
    }

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

    private function getParentProductIdByOfferId(int $offerId, int $offerIblockId): int
    {
        if (!class_exists('\CCatalogSKU')) {
            return 0;
        }

        $info = \CCatalogSKU::GetInfoByOfferIBlock($offerIblockId);

        if (empty($info['SKU_PROPERTY_ID'])) {
            return 0;
        }

        $skuPropertyId = (int)$info['SKU_PROPERTY_ID'];

        $propertyResult = \CIBlockElement::GetProperty(
            $offerIblockId,
            $offerId,
            [],
            [
                'ID' => $skuPropertyId,
            ]
        );

        $property = $propertyResult->Fetch();

        return $property ? (int)$property['VALUE'] : 0;
    }
}