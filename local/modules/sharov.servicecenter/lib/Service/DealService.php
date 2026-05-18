<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Crm\DealTable;
use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;

class DealService
{
    /**
     * Проверяет, является ли стадия финальной.
     *
     * @param string $stageId
     * @return bool
     */
    public function isFinalStage(string $stageId): bool
    {
        return in_array($stageId, ModuleSettings::getFinalStageIds(), true);
    }

    /**
     * Ищет открытые сделки по автомобилю.
     *
     * @param int $carId
     * @param int|null $excludeDealId
     * @return array
     */
    public function findOpenDealsByCarId(int $carId, ?int $excludeDealId = null): array
    {
        Loader::includeModule('crm');

        $filter = [
            '=UF_CRM_SC_CAR_ID' => $carId,
            '!@STAGE_ID' => ModuleSettings::getFinalStageIds(),
        ];

        $categoryId = ModuleSettings::getServiceCategoryId();
        if ($categoryId > 0) {
            $filter['=CATEGORY_ID'] = $categoryId;
        }

        if ($excludeDealId !== null) {
            $filter['!ID'] = $excludeDealId;
        }

        return DealTable::getList([
            'select' => [
                'ID',
                'TITLE',
                'ASSIGNED_BY_ID',
                'STAGE_ID',
                'CATEGORY_ID',
            ],
            'filter' => $filter,
            'order' => [
                'ID' => 'DESC',
            ],
        ])->fetchAll();
    }
}
