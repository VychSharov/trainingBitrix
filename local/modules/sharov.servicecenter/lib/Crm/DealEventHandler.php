<?php

namespace Sharov\ServiceCenter\Crm;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sharov\ServiceCenter\Service\NotificationService;

class DealEventHandler
{
    private const CAR_FIELD = 'UF_CRM_SC_CAR_ID';
    private const SERVICE_ORDER_FIELD = 'UF_CRM_SC_IS_SERVICE_ORDER';

    /**
     * Проверка перед созданием сделки.
     *
     * @param array $fields
     * @return bool
     */
    public static function onBeforeDealAdd(array &$fields): bool
    {
        Loc::loadMessages(__FILE__);

        return self::checkOpenServiceOrders($fields, null);
    }

    /**
     * Проверка перед обновлением сделки.
     *
     * @param array $fields
     * @return bool
     */
    public static function onBeforeDealUpdate(array &$fields): bool
    {
        Loc::loadMessages(__FILE__);

        $dealId = isset($fields['ID']) ? (int)$fields['ID'] : null;

        return self::checkOpenServiceOrders($fields, $dealId);
    }

    /**
     * Запрещает создать/сохранить сделку, если по машине уже есть открытый сервисный заказ-наряд.
     *
     * Важно:
     * обычные сделки по этому же автомобилю НЕ блокируем.
     * Блокируем только сделки, у которых включено поле "Сервисный заказ-наряд".
     *
     * @param array $fields
     * @param int|null $dealId
     * @return bool
     */
    private static function checkOpenServiceOrders(array &$fields, ?int $dealId): bool
    {
        global $APPLICATION;

        if (!Loader::includeModule('crm')) {
            return true;
        }

        $mergedFields = self::mergeWithCurrentDealFields($fields, $dealId);

        $isServiceOrder = self::isTruthy($mergedFields[self::SERVICE_ORDER_FIELD] ?? 0);

        /*
         * Если это НЕ сервисный заказ-наряд, дубль не проверяем.
         * Обычные сделки по тому же автомобилю создавать можно.
         */
        if (!$isServiceOrder) {
            return true;
        }

        $carId = (int)($mergedFields[self::CAR_FIELD] ?? 0);

        if ($carId <= 0) {
            return true;
        }

        $openDeals = self::findOpenServiceOrdersByCarId($carId, $dealId);

        if (empty($openDeals)) {
            return true;
        }

        $message = Loc::getMessage('SHAROV_SC_DUPLICATE_OPEN_DEAL_ERROR')
            ?: 'По этому автомобилю уже есть открытый сервисный заказ-наряд. Закройте предыдущий заказ-наряд.';

        $APPLICATION->ThrowException($message);

        $assignedById = (int)($mergedFields['ASSIGNED_BY_ID'] ?? 0);

        if ($assignedById > 0) {
            $notificationService = new NotificationService();
            $notificationService->notifyDuplicateOpenDeal($assignedById, $openDeals[0]);
        }

        return false;
    }

    /**
     * При обновлении в $fields могут прийти не все поля сделки.
     * Поэтому подмешиваем текущие значения из базы.
     *
     * @param array $fields
     * @param int|null $dealId
     * @return array
     */
    private static function mergeWithCurrentDealFields(array $fields, ?int $dealId): array
    {
        if (!$dealId || $dealId <= 0) {
            return $fields;
        }

        $currentFields = self::getCurrentDealFields($dealId);

        /*
         * Важно: новые значения из $fields должны перекрывать старые.
         */
        return array_merge($currentFields, $fields);
    }

    /**
     * Возвращает текущие значения нужных полей сделки.
     *
     * @param int $dealId
     * @return array
     */
    private static function getCurrentDealFields(int $dealId): array
    {
        $result = \CCrmDeal::GetListEx(
            [],
            [
                '=ID' => $dealId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                'CATEGORY_ID',
                'ASSIGNED_BY_ID',
                self::CAR_FIELD,
                self::SERVICE_ORDER_FIELD,
            ]
        );

        $deal = $result->Fetch();

        return $deal ?: [];
    }

    /**
     * Ищет открытые сервисные заказ-наряды по автомобилю.
     *
     * @param int $carId
     * @param int|null $excludeDealId
     * @return array
     */
    private static function findOpenServiceOrdersByCarId(int $carId, ?int $excludeDealId = null): array
    {
        $filter = [
            'CHECK_PERMISSIONS' => 'N',
            '=' . self::CAR_FIELD => $carId,
            '=' . self::SERVICE_ORDER_FIELD => 1,
        ];

        if ($excludeDealId && $excludeDealId > 0) {
            $filter['!ID'] = $excludeDealId;
        }

        $result = \CCrmDeal::GetListEx(
            [
                'DATE_CREATE' => 'DESC',
            ],
            $filter,
            false,
            false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                'CATEGORY_ID',
                'ASSIGNED_BY_ID',
                'DATE_CREATE',
                self::CAR_FIELD,
                self::SERVICE_ORDER_FIELD,
            ]
        );

        $openDeals = [];

        while ($deal = $result->Fetch()) {
            $stageId = (string)($deal['STAGE_ID'] ?? '');

            if (self::isFinalDealStage($stageId)) {
                continue;
            }

            $openDeals[] = [
                'ID' => (int)$deal['ID'],
                'TITLE' => (string)$deal['TITLE'],
                'STAGE_ID' => $stageId,
                'CATEGORY_ID' => (int)($deal['CATEGORY_ID'] ?? 0),
                'ASSIGNED_BY_ID' => (int)($deal['ASSIGNED_BY_ID'] ?? 0),
                'DATE_CREATE' => (string)($deal['DATE_CREATE'] ?? ''),
                self::CAR_FIELD => (int)($deal[self::CAR_FIELD] ?? 0),
                self::SERVICE_ORDER_FIELD => $deal[self::SERVICE_ORDER_FIELD] ?? 0,
            ];
        }

        return $openDeals;
    }

    /**
     * Проверяет, является ли стадия сделки финальной.
     *
     * @param string $stageId
     * @return bool
     */
    private static function isFinalDealStage(string $stageId): bool
    {
        global $DB;

        if ($stageId === '') {
            return false;
        }

        /*
         * Быстрый fallback для типовых стадий Bitrix.
         */
        if (
            $stageId === 'WON'
            || $stageId === 'LOSE'
            || strpos($stageId, ':WON') !== false
            || strpos($stageId, ':LOSE') !== false
            || strpos($stageId, ':SUCCESS') !== false
            || strpos($stageId, ':FAIL') !== false
        ) {
            return true;
        }

        $stageIdSql = $DB->ForSql($stageId);

        $result = $DB->Query("
            SELECT SEMANTICS
            FROM b_crm_status
            WHERE STATUS_ID = '{$stageIdSql}'
            LIMIT 1
        ");

        $row = $result->Fetch();

        if (!$row) {
            return false;
        }

        $semantics = (string)($row['SEMANTICS'] ?? '');

        /*
         * S = success, F = failure.
         */
        return in_array($semantics, ['S', 'F'], true);
    }

    /**
     * Нормализует значение boolean-поля Bitrix.
     *
     * @param mixed $value
     * @return bool
     */
    private static function isTruthy($value): bool
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = trim((string)$value);

        return in_array(
            mb_strtoupper($value),
            [
                '1',
                'Y',
                'YES',
                'TRUE',
                'ДА',
                'ON',
            ],
            true
        );
    }
}