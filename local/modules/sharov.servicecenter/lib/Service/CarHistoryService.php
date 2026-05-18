<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Crm\DealTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

class CarHistoryService
{
    /**
     * Возвращает историю сделок по автомобилю.
     *
     * @param int $carId
     * @return array
     */
    public function getHistoryByCarId(int $carId): array
    {
        Loader::includeModule('crm');

        $deals = DealTable::getList([
            'select' => [
                'ID',
                'TITLE',
                'DATE_CREATE',
                'STAGE_ID',
                'ASSIGNED_BY_ID',
                'OPPORTUNITY',
                'CURRENCY_ID',
                'UF_CRM_SC_CAR_ID',
            ],
            'filter' => [
                '=UF_CRM_SC_CAR_ID' => $carId,
            ],
            'order' => [
                'DATE_CREATE' => 'DESC',
            ],
        ])->fetchAll();

        foreach ($deals as &$deal) {
            $deal['RESPONSIBLE_NAME'] = $this->getUserName((int)$deal['ASSIGNED_BY_ID']);
            $deal['PRODUCTS'] = $this->getDealProducts((int)$deal['ID']);
        }
        unset($deal);

        return $deals;
    }

    /**
     * Возвращает список запчастей сделки.
     *
     * @param int $dealId
     * @return array
     */
    private function getDealProducts(int $dealId): array
    {
        if (!class_exists('\CCrmProductRow')) {
            return [];
        }

        return \CCrmProductRow::LoadRows('D', $dealId) ?: [];
    }

    /**
     * Возвращает имя ответственного пользователя.
     *
     * @param int $userId
     * @return string
     */
    private function getUserName(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $user = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
            'filter' => ['=ID' => $userId],
            'limit' => 1,
        ])->fetch();

        if (!$user) {
            return 'ID ' . $userId;
        }

        $name = trim(implode(' ', array_filter([
            $user['LAST_NAME'] ?? '',
            $user['NAME'] ?? '',
            $user['SECOND_NAME'] ?? '',
        ])));

        return $name !== '' ? $name : (string)$user['LOGIN'];
    }
}
