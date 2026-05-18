<?php

namespace Sharov\ServiceCenter\Service;

use Sharov\ServiceCenter\Infrastructure\ModuleSettings;

class ApproverResolver
{
    /**
     * Определяет согласующего закупки без хардкода ID пользователей.
     *
     * Логика:
     * 1. Берем первого активного пользователя из группы закупщиков по STRING_ID группы.
     * 2. Если закупщиков нет, берем первого активного пользователя из группы начальников закупок.
     * 3. Если никого нет, возвращаем 0, а вызывающий код логирует ошибку настройки.
     *
     * @return int
     */
    public function resolve(): int
    {
        $resolver = new CrmAutoResolver();

        $purchaseUsers = $resolver->getActiveUserIdsByGroupCode(ModuleSettings::getPurchaseGroupCode());
        if (!empty($purchaseUsers)) {
            return (int)$purchaseUsers[0];
        }

        $headUsers = $resolver->getActiveUserIdsByGroupCode(ModuleSettings::getPurchaseHeadGroupCode());
        if (!empty($headUsers)) {
            return (int)$headUsers[0];
        }

        return 0;
    }
}
