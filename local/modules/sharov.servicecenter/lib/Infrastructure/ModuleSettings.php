<?php

namespace Sharov\ServiceCenter\Infrastructure;

use Bitrix\Main\Config\Option;
use Sharov\ServiceCenter\Service\CrmAutoResolver;

class ModuleSettings
{
    public const MODULE_ID = 'sharov.servicecenter';

    public const DEFAULT_SERVICE_CATEGORY_NAME = 'Сервисное обслуживание';
    public const DEFAULT_PURCHASE_TYPE_TITLE = 'Заявка на закупку';
    public const DEFAULT_PURCHASE_GROUP_CODE = 'SERVICECENTER_PURCHASERS';
    public const DEFAULT_PURCHASE_HEAD_GROUP_CODE = 'SERVICECENTER_PURCHASE_HEAD';
    public const DEFAULT_RANDOM_QUANTITY_URL = 'https://www.random.org/integers/?num=1&min=0&max=10&col=1&base=10&format=plain&rnd=new';

    private const OPTION_SERVICE_CATEGORY_ID = 'service_category_id';
    private const OPTION_SERVICE_CATEGORY_NAME = 'service_category_name';
    private const OPTION_FINAL_STAGE_IDS = 'final_stage_ids';
    private const OPTION_TRACKED_PRODUCT_IDS = 'tracked_product_ids';
    private const OPTION_PURCHASE_GROUP_CODE = 'purchase_group_code';
    private const OPTION_PURCHASE_HEAD_GROUP_CODE = 'purchase_head_group_code';
    private const OPTION_PURCHASE_ENTITY_TYPE_ID = 'purchase_entity_type_id';
    private const OPTION_PURCHASE_ENTITY_TYPE_TITLE = 'purchase_entity_type_title';
    private const OPTION_PURCHASE_STAGE_APPROVED = 'purchase_stage_approved';
    private const OPTION_PURCHASE_STAGE_DONE = 'purchase_stage_done';
    private const OPTION_PURCHASE_STAGE_REJECTED = 'purchase_stage_rejected';
    private const OPTION_PURCHASE_STAGE_APPROVED_NAME = 'purchase_stage_approved_name';
    private const OPTION_PURCHASE_STAGE_DONE_NAME = 'purchase_stage_done_name';
    private const OPTION_PURCHASE_STAGE_REJECTED_NAME = 'purchase_stage_rejected_name';
    private const OPTION_EXTERNAL_QUANTITY_URL = 'external_quantity_url';

    /**
     * Возвращает ID направления сделок. Сначала берет настройку, потом ищет по названию.
     *
     * @return int
     */
    public static function getServiceCategoryId(): int
    {
        $configuredId = self::getInt(self::OPTION_SERVICE_CATEGORY_ID);
        if ($configuredId > 0) {
            return $configuredId;
        }

        return (new CrmAutoResolver())->findDealCategoryIdByName(self::getServiceCategoryName());
    }

    /**
     * Возвращает название сервисного направления сделок.
     *
     * @return string
     */
    public static function getServiceCategoryName(): string
    {
        return self::getString(self::OPTION_SERVICE_CATEGORY_NAME, self::DEFAULT_SERVICE_CATEGORY_NAME);
    }

    /**
     * Возвращает список финальных стадий сервисной воронки.
     *
     * @return array
     */
    public static function getFinalStageIds(): array
    {
        $configuredStageIds = self::getArray(self::OPTION_FINAL_STAGE_IDS);
        if (!empty($configuredStageIds)) {
            return $configuredStageIds;
        }

        return (new CrmAutoResolver())->findFinalDealStageIds(self::getServiceCategoryId());
    }

    /**
     * Возвращает ID товаров-запчастей, для которых агент обновляет остатки.
     *
     * ID не хардкодятся в PHP. Они задаются в настройках модуля.
     *
     * @return array
     */
    public static function getTrackedProductIds(): array
    {
        return self::getIntArray(self::OPTION_TRACKED_PRODUCT_IDS);
    }

    /**
     * Возвращает символьный код группы закупщиков.
     *
     * @return string
     */
    public static function getPurchaseGroupCode(): string
    {
        return self::getString(self::OPTION_PURCHASE_GROUP_CODE, self::DEFAULT_PURCHASE_GROUP_CODE);
    }

    /**
     * Возвращает символьный код группы начальника закупок.
     *
     * @return string
     */
    public static function getPurchaseHeadGroupCode(): string
    {
        return self::getString(self::OPTION_PURCHASE_HEAD_GROUP_CODE, self::DEFAULT_PURCHASE_HEAD_GROUP_CODE);
    }

    /**
     * Возвращает entityTypeId смарт-процесса. Сначала берет настройку, потом ищет по названию.
     *
     * @return int
     */
    public static function getPurchaseEntityTypeId(): int
    {
        $configuredEntityTypeId = self::getInt(self::OPTION_PURCHASE_ENTITY_TYPE_ID);
        if ($configuredEntityTypeId > 0) {
            return $configuredEntityTypeId;
        }

        return (new CrmAutoResolver())->findDynamicTypeIdByTitle(self::getPurchaseEntityTypeTitle());
    }

    /**
     * Возвращает название смарт-процесса заявок на закупку.
     *
     * @return string
     */
    public static function getPurchaseEntityTypeTitle(): string
    {
        return self::getString(self::OPTION_PURCHASE_ENTITY_TYPE_TITLE, self::DEFAULT_PURCHASE_TYPE_TITLE);
    }

    /**
     * Возвращает стадию "Одобрено".
     *
     * @return string
     */
    public static function getPurchaseApprovedStageId(): string
    {
        return self::getPurchaseStageId(
            self::OPTION_PURCHASE_STAGE_APPROVED,
            self::OPTION_PURCHASE_STAGE_APPROVED_NAME,
            'Одобрено'
        );
    }

    /**
     * Возвращает стадию "Выполнено".
     *
     * @return string
     */
    public static function getPurchaseDoneStageId(): string
    {
        return self::getPurchaseStageId(
            self::OPTION_PURCHASE_STAGE_DONE,
            self::OPTION_PURCHASE_STAGE_DONE_NAME,
            'Выполнено'
        );
    }

    /**
     * Возвращает стадию "Отклонено".
     *
     * @return string
     */
    public static function getPurchaseRejectedStageId(): string
    {
        return self::getPurchaseStageId(
            self::OPTION_PURCHASE_STAGE_REJECTED,
            self::OPTION_PURCHASE_STAGE_REJECTED_NAME,
            'Отклонено'
        );
    }

    /**
     * Возвращает URL внешнего сервиса остатков.
     *
     * @return string
     */
    public static function getExternalQuantityUrl(): string
    {
        return self::getString(self::OPTION_EXTERNAL_QUANTITY_URL, self::DEFAULT_RANDOM_QUANTITY_URL);
    }

    /**
     * Возвращает список имен настроек для страницы options.php.
     *
     * @return array
     */
    public static function getOptionNames(): array
    {
        return [
            self::OPTION_SERVICE_CATEGORY_ID,
            self::OPTION_SERVICE_CATEGORY_NAME,
            self::OPTION_FINAL_STAGE_IDS,
            self::OPTION_TRACKED_PRODUCT_IDS,
            self::OPTION_PURCHASE_GROUP_CODE,
            self::OPTION_PURCHASE_HEAD_GROUP_CODE,
            self::OPTION_PURCHASE_ENTITY_TYPE_ID,
            self::OPTION_PURCHASE_ENTITY_TYPE_TITLE,
            self::OPTION_PURCHASE_STAGE_APPROVED,
            self::OPTION_PURCHASE_STAGE_DONE,
            self::OPTION_PURCHASE_STAGE_REJECTED,
            self::OPTION_PURCHASE_STAGE_APPROVED_NAME,
            self::OPTION_PURCHASE_STAGE_DONE_NAME,
            self::OPTION_PURCHASE_STAGE_REJECTED_NAME,
            self::OPTION_EXTERNAL_QUANTITY_URL,
        ];
    }

    /**
     * Возвращает строковую настройку модуля.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function getString(string $name, string $default = ''): string
    {
        return trim((string)Option::get(self::MODULE_ID, $name, $default));
    }

    /**
     * Возвращает числовую настройку модуля.
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    public static function getInt(string $name, int $default = 0): int
    {
        return (int)Option::get(self::MODULE_ID, $name, (string)$default);
    }

    /**
     * Возвращает настройку-список строк.
     *
     * @param string $name
     * @return array
     */
    public static function getArray(string $name): array
    {
        $value = self::getString($name);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * Возвращает настройку-список чисел.
     *
     * @param string $name
     * @return array
     */
    public static function getIntArray(string $name): array
    {
        return array_values(array_filter(array_map('intval', self::getArray($name))));
    }

    /**
     * Сохраняет настройку модуля.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function set(string $name, string $value): void
    {
        Option::set(self::MODULE_ID, $name, trim($value));
    }

    /**
     * Возвращает ID стадии заявки: сначала из настроек, затем через поиск по названию.
     *
     * @param string $stageIdOption
     * @param string $stageNameOption
     * @param string $defaultStageName
     * @return string
     */
    private static function getPurchaseStageId(
        string $stageIdOption,
        string $stageNameOption,
        string $defaultStageName
    ): string {
        $configuredStageId = self::getString($stageIdOption);
        if ($configuredStageId !== '') {
            return $configuredStageId;
        }

        $stageName = self::getString($stageNameOption, $defaultStageName);

        return (new CrmAutoResolver())->findDynamicStageIdByName(
            self::getPurchaseEntityTypeId(),
            $stageName
        );
    }
}
