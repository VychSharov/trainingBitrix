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

        return self::checkOpenServiceOrders($fields, self::extractDealId($fields));
    }

    /**
     * Запрещает создать/сохранить заказ-наряд, если по автомобилю уже есть открытый заказ-наряд.
     *
     * @param array $fields
     * @param int|null $dealId
     * @return bool
     */
    private static function checkOpenServiceOrders(array &$fields, ?int $dealId): bool
    {
        if (!Loader::includeModule('crm')) {
            return true;
        }

        $mergedFields = self::mergeWithCurrentDealFields($fields, $dealId);

        /*
         * Обычные сделки по этому автомобилю не блокируем.
         * Контроль включается только для сделок с галкой "Сервисный заказ-наряд".
         */
        if (!self::isTruthy($mergedFields[self::SERVICE_ORDER_FIELD] ?? 0)) {
            return true;
        }

        $carId = (int)($mergedFields[self::CAR_FIELD] ?? 0);

        if ($carId <= 0) {
            return true;
        }

        /*
         * Закрытие текущего заказ-наряда в финальную стадию должно быть разрешено.
         */
        $stageId = (string)($mergedFields['STAGE_ID'] ?? '');
        if ($stageId !== '' && self::isFinalDealStage($stageId)) {
            return true;
        }

        $openDeals = self::findOpenServiceOrdersByCarId($carId, $dealId);

        if (empty($openDeals)) {
            return true;
        }

        $openDeal = $openDeals[0];

        /*
         * ВАЖНО:
         * Сообщение формируем прямо здесь без ID, чтобы Битрикс не показывал технический текст
         * и чтобы в ошибке не было "ID=...".
         */
        $message = self::buildDuplicateOpenDealMessage($openDeal);

        self::setCrmError($fields, $message);

        $assignedById = (int)($mergedFields['ASSIGNED_BY_ID'] ?? 0);

        if ($assignedById > 0) {
            (new NotificationService())->notifyDuplicateOpenDeal($assignedById, $openDeal);
        }

        return false;
    }

    /**
     * Формирует пользовательское сообщение об ошибке дубля заказ-наряда.
     *
     * @param array $openDeal
     * @return string
     */
    private static function buildDuplicateOpenDealMessage(array $openDeal): string
    {
        $title = trim((string)($openDeal['TITLE'] ?? ''));

        if ($title === '') {
            $title = 'без названия';
        }

        return 'По этому автомобилю уже есть незакрытый сервисный заказ-наряд: "'
            . $title
            . '". Закройте предыдущий заказ-наряд перед созданием нового.';
    }

    /**
     * Записывает пользовательское сообщение ошибки в формат, который CRM показывает вместо технического текста.
     *
     * @param array $fields
     * @param string $message
     * @return void
     */
    private static function setCrmError(array &$fields, string $message): void
    {
        global $APPLICATION;

        /*
         * RESULT_MESSAGE нужен CRM, чтобы показать нормальный текст ошибки,
         * а не "Обновление сделки отменено обработчиком события...".
         */
        $fields['RESULT_MESSAGE'] = $message;

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'ThrowException')) {
            $APPLICATION->ThrowException($message);
        }
    }

    /**
     * Достаёт ID сделки из массива события.
     *
     * @param array $fields
     * @return int|null
     */
    private static function extractDealId(array $fields): ?int
    {
        foreach (['ID', 'id', 'DEAL_ID', 'dealId'] as $key) {
            if (isset($fields[$key]) && (int)$fields[$key] > 0) {
                return (int)$fields[$key];
            }
        }

        return null;
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
            if (!self::isTruthy($deal[self::SERVICE_ORDER_FIELD] ?? 0)) {
                continue;
            }

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

        return in_array((string)($row['SEMANTICS'] ?? ''), ['S', 'F'], true);
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
