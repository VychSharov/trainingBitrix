<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;

class SetupValidator
{
    /**
     * Проверяет базовую готовность проекта после установки.
     *
     * @return array
     */
    public function validate(): array
    {
        $errors = [];

        foreach (['crm', 'catalog', 'im'] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                $errors[] = sprintf('Не подключен модуль %s', $moduleId);
            }
        }

        if (!$this->isCarTableExists()) {
            $errors[] = 'Не найдена таблица b_sharov_sc_car';
        }

        if (ModuleSettings::getServiceCategoryId() <= 0) {
            $errors[] = sprintf(
                'Не найдено направление сделок "%s". Создайте его или укажите ID в настройках модуля.',
                ModuleSettings::getServiceCategoryName()
            );
        }

        if (empty(ModuleSettings::getFinalStageIds())) {
            $errors[] = 'Не найдены финальные стадии сервисной воронки. Укажите их в настройках, если автопоиск не сработал.';
        }

        if (empty(ModuleSettings::getTrackedProductIds())) {
            $errors[] = 'Не задан список товаров-запчастей для синхронизации остатков. Укажите ID товаров в настройках модуля.';
        }

        if ($this->resolvePurchaseApprover() <= 0) {
            $errors[] = sprintf(
                'Не найден активный закупщик. Добавьте пользователей в группу %s или %s.',
                ModuleSettings::getPurchaseGroupCode(),
                ModuleSettings::getPurchaseHeadGroupCode()
            );
        }

        if (ModuleSettings::getPurchaseEntityTypeId() <= 0) {
            $errors[] = sprintf(
                'Не найден смарт-процесс "%s". Создайте его или укажите entityTypeId в настройках.',
                ModuleSettings::getPurchaseEntityTypeTitle()
            );
        }

        if (ModuleSettings::getPurchaseApprovedStageId() === '') {
            $errors[] = 'Не найдена стадия одобрения заявки на закупку.';
        }

        if (ModuleSettings::getPurchaseDoneStageId() === '') {
            $errors[] = 'Не найдена стадия выполнения заявки на закупку.';
        }

        if (ModuleSettings::getPurchaseRejectedStageId() === '') {
            $errors[] = 'Не найдена стадия отклонения заявки на закупку.';
        }

        if (ModuleSettings::getExternalQuantityUrl() === '') {
            $errors[] = 'Не задан URL внешнего сервиса остатков.';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Проверяет наличие таблицы автомобилей.
     *
     * @return bool
     */
    private function isCarTableExists(): bool
    {
        $connection = Application::getConnection();

        return $connection->isTableExists('b_sharov_sc_car');
    }

    /**
     * Проверяет, найден ли согласующий закупки.
     *
     * @return int
     */
    private function resolvePurchaseApprover(): int
    {
        try {
            return (new ApproverResolver())->resolve();
        } catch (\Throwable $exception) {
            return 0;
        }
    }
}
