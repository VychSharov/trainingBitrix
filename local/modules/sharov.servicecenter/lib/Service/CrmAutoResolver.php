<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Loader;

class CrmAutoResolver
{
    /**
     * Ищет ID направления сделок по названию.
     *
     * @param string $name
     * @return int
     */
    public function findDealCategoryIdByName(string $name): int
    {
        if ($name === '' || !Loader::includeModule('crm')) {
            return 0;
        }

        try {
            if (class_exists('\Bitrix\Crm\Category\DealCategory')) {
                $categories = \Bitrix\Crm\Category\DealCategory::getAll(true);
                foreach ($categories as $category) {
                    if ((string)($category['NAME'] ?? '') === $name) {
                        return (int)($category['ID'] ?? 0);
                    }
                }
            }
        } catch (\Throwable $exception) {
            return 0;
        }

        return 0;
    }

    /**
     * Ищет финальные стадии направления сделок без привязки к конкретным ID.
     *
     * @param int $categoryId
     * @return array
     */
    public function findFinalDealStageIds(int $categoryId): array
    {
        if (!Loader::includeModule('crm')) {
            return [];
        }

        $entityId = $categoryId > 0 ? 'DEAL_STAGE_' . $categoryId : 'DEAL_STAGE';
        $stageIds = [];

        try {
            if (class_exists('\Bitrix\Crm\StatusTable')) {
                $rows = \Bitrix\Crm\StatusTable::getList([
                    'select' => ['STATUS_ID', 'SEMANTICS', 'ENTITY_ID'],
                    'filter' => ['=ENTITY_ID' => $entityId],
                ])->fetchAll();

                foreach ($rows as $row) {
                    $statusId = (string)($row['STATUS_ID'] ?? '');
                    $semantics = strtoupper((string)($row['SEMANTICS'] ?? ''));

                    if ($statusId === '') {
                        continue;
                    }

                    if (
                        in_array($semantics, ['S', 'F', 'SUCCESS', 'FAILURE'], true)
                        || preg_match('/(^|:)WON$/', $statusId)
                        || preg_match('/(^|:)LOSE$/', $statusId)
                    ) {
                        $stageIds[] = $statusId;
                    }
                }
            }
        } catch (\Throwable $exception) {
            return [];
        }

        return array_values(array_unique($stageIds));
    }

    /**
     * Ищет entityTypeId смарт-процесса по названию.
     *
     * @param string $title
     * @return int
     */
    public function findDynamicTypeIdByTitle(string $title): int
    {
        if ($title === '' || !Loader::includeModule('crm')) {
            return 0;
        }

        try {
            if (class_exists('\Bitrix\Crm\Model\Dynamic\TypeTable')) {
                $row = \Bitrix\Crm\Model\Dynamic\TypeTable::getList([
                    'select' => ['ENTITY_TYPE_ID', 'TITLE'],
                    'filter' => ['=TITLE' => $title],
                    'limit' => 1,
                ])->fetch();

                return $row ? (int)$row['ENTITY_TYPE_ID'] : 0;
            }
        } catch (\Throwable $exception) {
            return 0;
        }

        return 0;
    }

    /**
     * Ищет ID стадии смарт-процесса по названию стадии.
     *
     * @param int $entityTypeId
     * @param string $stageName
     * @return string
     */
    public function findDynamicStageIdByName(int $entityTypeId, string $stageName): string
    {
        if ($entityTypeId <= 0 || $stageName === '' || !Loader::includeModule('crm')) {
            return '';
        }

        try {
            if (class_exists('\Bitrix\Crm\Service\Container')) {
                $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
                if ($factory && method_exists($factory, 'getStages')) {
                    $stages = $factory->getStages();
                    if ($stages && method_exists($stages, 'getAll')) {
                        foreach ($stages->getAll() as $stage) {
                            if (
                                method_exists($stage, 'getName')
                                && method_exists($stage, 'getStatusId')
                                && (string)$stage->getName() === $stageName
                            ) {
                                return (string)$stage->getStatusId();
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $exception) {
            return '';
        }

        return '';
    }

    /**
     * Возвращает активных пользователей из группы по символьному коду группы.
     *
     * @param string $groupCode
     * @return array
     */
    public function getActiveUserIdsByGroupCode(string $groupCode): array
    {
        if ($groupCode === '') {
            return [];
        }

        $groupId = $this->findGroupIdByCode($groupCode);
        if ($groupId <= 0) {
            return [];
        }

        $userIds = [];
        $by = 'id';
        $order = 'asc';
        $filter = [
            'ACTIVE' => 'Y',
            'GROUPS_ID' => [$groupId],
        ];
        $params = [
            'FIELDS' => ['ID', 'ACTIVE'],
        ];

        $users = \CUser::GetList($by, $order, $filter, $params);
        while ($user = $users->Fetch()) {
            $userIds[] = (int)$user['ID'];
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Ищет ID группы по символьному коду.
     *
     * @param string $groupCode
     * @return int
     */
    public function findGroupIdByCode(string $groupCode): int
    {
        if ($groupCode === '') {
            return 0;
        }

        $by = 'c_sort';
        $order = 'asc';
        $groups = \CGroup::GetList($by, $order, ['STRING_ID' => $groupCode]);
        $group = $groups->Fetch();

        return $group ? (int)$group['ID'] : 0;
    }
}
