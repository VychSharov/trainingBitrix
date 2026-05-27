<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Sharov\ServiceCenter\Infrastructure\Logger;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;
use Throwable;

class PurchaseRequestService
{
    /**
     * Создаёт автоматическую заявку на закупку.
     *
     * @param int $productId
     * @param int $quantity
     * @return int
     */
    public function createAutoPurchaseRequest(int $productId, int $quantity): int
    {
        $approverId = (new ApproverResolver())->resolve();
        $entityTypeId = ModuleSettings::getPurchaseEntityTypeId();

        if ($entityTypeId <= 0) {
            Logger::error('Purchase smart process is not configured', [
                'productId' => $productId,
                'quantity' => $quantity,
            ]);

            return 0;
        }

        if ($approverId <= 0) {
            Logger::error('Purchase approver is not configured', [
                'productId' => $productId,
                'quantity' => $quantity,
            ]);
        }

        $doneStageId = $this->getDoneStageId($entityTypeId);

        $fields = [
            'TITLE' => 'Автозакупка запчасти: ' . $this->getProductName($productId),
            'UF_SC_AUTO_CREATED' => 1,
            'UF_SC_SOURCE_PRODUCT_ID' => $productId,
            'UF_SC_QUANTITY' => $quantity,
        ];

        if ($doneStageId !== '') {
            $fields['STAGE_ID'] = $doneStageId;
        }

        if ($approverId > 0) {
            $fields['ASSIGNED_BY_ID'] = $approverId;
            $fields['UF_SC_APPROVER_ID'] = $approverId;
        }

        $requestId = $this->createSmartProcessItem($entityTypeId, $fields);

        if ($requestId > 0) {
            $this->setProductRows($entityTypeId, $requestId, [
                [
                    'PRODUCT_ID' => $productId,
                    'QUANTITY' => $quantity,
                ],
            ]);

            $this->markProcessed($entityTypeId, $requestId, 'AUTO_DONE');
        }

        if ($approverId > 0) {
            (new NotificationService())->notifyAutoPurchaseDone($approverId, $productId, $quantity);
        }

        return $requestId;
    }

    /**
     * Создаёт ручную заявку на закупку.
     *
     * @param int $requesterId
     * @param array $products
     * @return int
     */
    public function createManualPurchaseRequest(int $requesterId, array $products): int
    {
        $approverId = (new ApproverResolver())->resolve();
        $entityTypeId = ModuleSettings::getPurchaseEntityTypeId();

        if ($entityTypeId <= 0) {
            throw new \RuntimeException('Не настроен смарт-процесс заявки на закупку');
        }

        if ($approverId <= 0) {
            throw new \RuntimeException('Не настроена группа закупщиков или начальника закупок');
        }

        $firstProduct = $products[0] ?? [];

        $fields = [
            'TITLE' => 'Ручная заявка на закупку',
            'ASSIGNED_BY_ID' => $approverId,
            'UF_SC_REQUESTER_ID' => $requesterId,
            'UF_SC_APPROVER_ID' => $approverId,
            'UF_SC_AUTO_CREATED' => 0,
        ];

        if (!empty($firstProduct['PRODUCT_ID'])) {
            $fields['UF_SC_SOURCE_PRODUCT_ID'] = (int)$firstProduct['PRODUCT_ID'];
        }

        if (!empty($firstProduct['QUANTITY'])) {
            $fields['UF_SC_QUANTITY'] = (float)$firstProduct['QUANTITY'];
        }

        $requestId = $this->createSmartProcessItem($entityTypeId, $fields);

        if ($requestId > 0) {
            $this->setProductRows($entityTypeId, $requestId, $products);
        }

        return $requestId;
    }

    /**
     * Обрабатывает заявки, которые вручную перевели в "Одобрено" или "Отклонено".
     *
     * @return array
     */
    public function processPending(): array
    {
        Loader::includeModule('crm');

        $entityTypeId = ModuleSettings::getPurchaseEntityTypeId();

        if ($entityTypeId <= 0) {
            throw new \RuntimeException('Не настроен смарт-процесс заявки на закупку');
        }

        $approvedStageId = $this->findStageIdByName($entityTypeId, 'Одобрено');
        $doneStageId = $this->getDoneStageId($entityTypeId);

        $rejectedStageId = ModuleSettings::getPurchaseRejectedStageId();

        if ($rejectedStageId === '') {
            $rejectedStageId = $this->findStageIdByName($entityTypeId, 'Отклонено');
        }

        if ($approvedStageId === '') {
            throw new \RuntimeException('Не найдена стадия "Одобрено"');
        }

        if ($doneStageId === '') {
            throw new \RuntimeException('Не найдена стадия "Выполнено"');
        }

        if ($rejectedStageId === '') {
            throw new \RuntimeException('Не найдена стадия "Отклонено"');
        }

        $factory = Container::getInstance()->getFactory($entityTypeId);

        if (!$factory) {
            throw new \RuntimeException('Не найдена фабрика смарт-процесса: ' . $entityTypeId);
        }

        $stageFilter = array_values(array_unique([
            $approvedStageId,
            $doneStageId,
            $rejectedStageId,
        ]));

        $items = $factory->getItems([
            'select' => ['*', 'UF_*'],
            'filter' => [
                '@STAGE_ID' => $stageFilter,
            ],
            'limit' => 100,
        ]);

        $result = [];

        foreach ($items as $item) {
            $itemId = (int)$item->getId();

            try {
                $processedStatus = $this->getProcessedStatus($entityTypeId, $itemId);

                /*
                 * Если закупка уже была выполнена, но заявка зависла в "Одобрено",
                 * не закупаем повторно, а только переводим в "Выполнено".
                 */
                if ($processedStatus === 'PROCESSING') {
                    $this->finishApprovedWithoutPurchase($entityTypeId, $itemId);

                    $result[] = [
                        'success' => true,
                        'itemId' => $itemId,
                        'message' => 'Заявка уже была закуплена ранее, стадия дотянута до Выполнено',
                    ];

                    continue;
                }

                if ($processedStatus !== '') {
                    continue;
                }

                $stageId = (string)$item->getStageId();

                /*
                 * Исправление для проверки:
                 * если пользователь сразу переводит заявку в "Выполнено",
                 * остаток тоже должен увеличиться.
                 */
                if ($stageId === $approvedStageId || $stageId === $doneStageId) {
                    $this->approve($itemId);

                    $result[] = [
                        'success' => true,
                        'itemId' => $itemId,
                        'message' => 'Заявка обработана, остаток увеличен, стадия Выполнено',
                    ];

                    continue;
                }

                if ($stageId === $rejectedStageId) {
                    $itemData = $item->getData();
                    $reason = trim((string)($itemData['UF_SC_REJECT_REASON'] ?? ''));

                    $this->reject($itemId, $reason);

                    $result[] = [
                        'success' => true,
                        'itemId' => $itemId,
                        'message' => 'Заявка отклонена и обработана',
                    ];
                }
            } catch (Throwable $exception) {
                $result[] = [
                    'success' => false,
                    'itemId' => $itemId,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Обрабатывает одобрение заявки.
     *
     * @param int $requestId
     * @return void
     */
    public function approve(int $requestId): void
    {
        Loader::includeModule('catalog');
        Loader::includeModule('iblock');

        $entityTypeId = ModuleSettings::getPurchaseEntityTypeId();

        if ($entityTypeId <= 0) {
            throw new \RuntimeException('Не настроен смарт-процесс заявки на закупку');
        }

        if ($this->isProcessed($entityTypeId, $requestId)) {
            return;
        }

        $itemData = $this->getSmartProcessItem($entityTypeId, $requestId);

        if (!$this->isApprovedByApprover($itemData)) {
            $this->returnToAgreementStage($entityTypeId, $requestId);

            $approverId = $this->extractUserId($itemData['UF_SC_APPROVER_ID'] ?? 0);
            $stageChangerId = $this->getStageChangerId($itemData);

            throw new \RuntimeException(
                'Заявка #' . $requestId
                . ' возвращена на согласование: одобрить закупку может только пользователь из поля "Согласующий" или начальник отдела закупок.'
                . ' Согласующий ID=' . $approverId
                . ', перевёл ID=' . $stageChangerId
            );
        }

        /*
         * Сначала помечаем как PROCESSING.
         * Так агент не сможет повторно накрутить остатки, если стадия не сменится.
         */
        $this->markProcessed($entityTypeId, $requestId, 'PROCESSING');

        $products = $this->getProductsForRequest($entityTypeId, $requestId, $itemData);

        if (empty($products)) {
            $this->removeProcessed($entityTypeId, $requestId);
            throw new \RuntimeException('В заявке #' . $requestId . ' не указаны запчасти');
        }

        $doneStageId = $this->getDoneStageId($entityTypeId);

        if ($doneStageId === '') {
            $this->removeProcessed($entityTypeId, $requestId);
            throw new \RuntimeException('Не настроена стадия "Выполнено"');
        }

        $stockService = new CatalogStockService();

        foreach ($products as $product) {
            $productId = (int)($product['PRODUCT_ID'] ?? 0);
            $quantity = (float)($product['QUANTITY'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $stockService->increaseQuantity($productId, $quantity);
        }

        $this->updateSmartProcessItem($entityTypeId, $requestId, [
            'STAGE_ID' => $doneStageId,
        ]);

        $this->markProcessed($entityTypeId, $requestId, 'APPROVED');

        $requesterId = (int)($itemData['UF_SC_REQUESTER_ID'] ?? 0);

        if ($requesterId > 0) {
            (new NotificationService())->notifyPurchaseApproved($requesterId, $requestId);
        }
    }

    /**
     * Обрабатывает отказ.
     *
     * @param int $requestId
     * @param string $reason
     * @return void
     */
    public function reject(int $requestId, string $reason): void
    {
        $entityTypeId = ModuleSettings::getPurchaseEntityTypeId();

        if ($entityTypeId <= 0) {
            throw new \RuntimeException('Не настроен смарт-процесс заявки на закупку');
        }

        if ($this->isProcessed($entityTypeId, $requestId)) {
            return;
        }

        $itemData = $this->getSmartProcessItem($entityTypeId, $requestId);

        $reason = trim($reason);

        if ($reason === '') {
            $agreementStageId = $this->findStageIdByName($entityTypeId, 'На согласовании');

            if ($agreementStageId !== '') {
                $this->updateSmartProcessItem($entityTypeId, $requestId, [
                    'STAGE_ID' => $agreementStageId,
                ]);
            }

            throw new \RuntimeException(
                'Отклонение без причины запрещено. Заявка #' . $requestId . ' возвращена на согласование.'
            );
        }

        $rejectedStageId = ModuleSettings::getPurchaseRejectedStageId();

        if ($rejectedStageId === '') {
            $rejectedStageId = $this->findStageIdByName($entityTypeId, 'Отклонено');
        }

        if ($rejectedStageId === '') {
            throw new \RuntimeException('Не настроена стадия отклонения заявки на закупку');
        }

        $this->updateSmartProcessItem($entityTypeId, $requestId, [
            'UF_SC_REJECT_REASON' => $reason,
            'STAGE_ID' => $rejectedStageId,
        ]);

        $this->markProcessed($entityTypeId, $requestId, 'REJECTED');

        $requesterId = (int)($itemData['UF_SC_REQUESTER_ID'] ?? 0);

        if ($requesterId > 0) {
            (new NotificationService())->notifyPurchaseRejected($requesterId, $requestId, $reason);
        }
    }


    /**
     * Проверяет, что заявку одобрил пользователь из поля "Согласующий"
     * или начальник отдела закупок.
     *
     * Важно:
     * стандартное поле "Ответственный" здесь не используется.
     *
     * @param array $itemData
     * @return bool
     */
    private function isApprovedByApprover(array $itemData): bool
    {
        $approverId = $this->extractUserId($itemData['UF_SC_APPROVER_ID'] ?? 0);
        $stageChangerId = $this->getStageChangerId($itemData);

        if ($stageChangerId <= 0) {
            return false;
        }

        /*
         * Исключение:
         * начальник отдела закупок может одобрить любую заявку,
         * даже если он не указан в поле "Согласующий".
         */
        if ($this->isPurchaseChief($stageChangerId)) {
            return true;
        }

        if ($approverId <= 0) {
            return false;
        }

        return $approverId === $stageChangerId;
    }

    /**
     * Нормализует значение пользовательского поля с пользователем.
     *
     * Bitrix может вернуть USER-поле как число, строку или массив.
     *
     * @param mixed $value
     * @return int
     */
    private function extractUserId($value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return (int)$value;
    }

    /**
     * Проверяет, может ли пользователь одобрять любые заявки на закупку.
     *
     * Право имеют:
     * 1. Администраторы;
     * 2. Директор сервисного центра;
     * 3. Начальники закупок сервисного центра.
     *
     * Обычный закупщик может одобрить только заявку,
     * где он указан в поле "Согласующий".
     *
     * @param int $userId
     * @return bool
     */
    private function isPurchaseChief(int $userId): bool
    {
        global $DB;

        if ($userId <= 0) {
            return false;
        }

        if (!class_exists('\CUser')) {
            return false;
        }

        $groupIds = \CUser::GetUserGroup($userId);

        if (!is_array($groupIds) || empty($groupIds)) {
            return false;
        }

        $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));

        if (empty($groupIds)) {
            return false;
        }

        /*
         * Группа 1 — стандартная группа администраторов Битрикса.
         */
        if (in_array(1, $groupIds, true)) {
            return true;
        }

        $groupIdsSql = implode(',', $groupIds);

        $result = $DB->Query("
            SELECT ID, NAME, STRING_ID
            FROM b_group
            WHERE ID IN ({$groupIdsSql})
        ");

        while ($group = $result->Fetch()) {
            $groupName = trim((string)($group['NAME'] ?? ''));
            $groupCode = trim((string)($group['STRING_ID'] ?? ''));

            /*
             * Проверка по символьному идентификатору группы.
             * Это самый надёжный вариант, если символьные коды заполнены.
             */
            if (in_array(
                $groupCode,
                [
                    'SERVICECENTER_DIRECTOR',
                    'SERVICECENTER_PURCHASE_HEAD',
                ],
                true
            )) {
                return true;
            }

            /*
             * Проверка по точному названию группы.
             */
            if (in_array(
                $groupName,
                [
                    'Администраторы',
                    'Директор сервисного центра',
                    'Начальники закупок сервисного центра',
                    'Начальник отдела закупок',
                    'Начальники закупок',
                    'Начальник закупок',
                    'Руководитель закупок',
                ],
                true
            )) {
                return true;
            }

            /*
             * Дополнительная гибкая проверка,
             * если название группы немного отличается.
             */
            $groupNameLower = mb_strtolower($groupName);

            if (
                mb_stripos($groupNameLower, 'директор') !== false
                && mb_stripos($groupNameLower, 'сервис') !== false
            ) {
                return true;
            }

            if (
                mb_stripos($groupNameLower, 'начальник') !== false
                && mb_stripos($groupNameLower, 'закуп') !== false
            ) {
                return true;
            }

            if (
                mb_stripos($groupNameLower, 'руководитель') !== false
                && mb_stripos($groupNameLower, 'закуп') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Возвращает пользователя, который последним двигал/изменял заявку.
     *
     * Для смарт-процессов Битрикс обычно использует поля MOVED_BY или UPDATED_BY.
     * На разных версиях портала названия могут отличаться, поэтому проверяем несколько вариантов.
     *
     * @param array $itemData
     * @return int
     */
    private function getStageChangerId(array $itemData): int
    {
        $candidateFields = [
            'MOVED_BY',
            'MOVED_BY_ID',
            'UPDATED_BY',
            'UPDATED_BY_ID',
            'MODIFY_BY_ID',
            'MODIFIED_BY',
            'LAST_MODIFIED_BY',
        ];

        foreach ($candidateFields as $fieldName) {
            $userId = (int)($itemData[$fieldName] ?? 0);

            if ($userId > 0) {
                return $userId;
            }
        }

        return 0;
    }

    /**
     * Возвращает заявку обратно на стадию "На согласовании".
     *
     * @param int $entityTypeId
     * @param int $requestId
     * @return void
     */
    private function returnToAgreementStage(int $entityTypeId, int $requestId): void
    {
        $agreementStageId = $this->findStageIdByName($entityTypeId, 'На согласовании');

        if ($agreementStageId === '') {
            /*
             * Фактическая стадия "На согласовании" у твоего процесса.
             */
            if ($entityTypeId === 1046) {
                $agreementStageId = 'DT1046_6:PREPARATION';
            }
        }

        if ($agreementStageId === '') {
            throw new \RuntimeException('Не найдена стадия "На согласовании"');
        }

        $this->updateSmartProcessItem($entityTypeId, $requestId, [
            'STAGE_ID' => $agreementStageId,
        ]);
    }

    /**
     * Возвращает название товара/запчасти по ID.
     *
     * @param int $productId
     * @return string
     */
    private function getProductName(int $productId): string
    {
        if ($productId <= 0) {
            return 'неизвестная запчасть';
        }

        if (!Loader::includeModule('iblock')) {
            return 'запчасть #' . $productId;
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
                'NAME',
            ]
        );

        $element = $result->Fetch();

        if (!$element || trim((string)$element['NAME']) === '') {
            return 'запчасть #' . $productId;
        }

        return (string)$element['NAME'];
    }

    /**
     * Создаёт элемент смарт-процесса.
     *
     * @param int $entityTypeId
     * @param array $fields
     * @return int
     */
    private function createSmartProcessItem(int $entityTypeId, array $fields): int
    {
        Loader::includeModule('crm');

        $factory = Container::getInstance()->getFactory($entityTypeId);

        if (!$factory) {
            throw new \RuntimeException('Не найдена фабрика смарт-процесса: ' . $entityTypeId);
        }

        $item = $factory->createItem($fields);
        $operation = $factory->getAddOperation($item);

        if (method_exists($operation, 'disableCheckAccess')) {
            $operation->disableCheckAccess();
        }

        if (method_exists($operation, 'disableCheckFieldsCheck')) {
            $operation->disableCheckFieldsCheck();
        }

        $result = $operation->launch();

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return (int)$item->getId();
    }

    /**
     * Возвращает элемент смарт-процесса как массив.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return array
     */
    private function getSmartProcessItem(int $entityTypeId, int $itemId): array
    {
        Loader::includeModule('crm');

        $factory = Container::getInstance()->getFactory($entityTypeId);

        if (!$factory) {
            throw new \RuntimeException('Не найдена фабрика смарт-процесса: ' . $entityTypeId);
        }

        $item = $factory->getItem($itemId);

        if (!$item) {
            throw new \RuntimeException('Не найдена заявка на закупку: ' . $itemId);
        }

        return $item->getData();
    }

    /**
     * Обновляет элемент смарт-процесса.
     *
     * Важно:
     * агент запускается не как пользователь-админ, поэтому D7-операция CRM
     * может не пройти по правам. Поэтому отключаем проверку доступа.
     * Если стадия всё равно не сменилась — делаем SQL fallback.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param array $fields
     * @return void
     */
    private function updateSmartProcessItem(int $entityTypeId, int $itemId, array $fields): void
    {
        Loader::includeModule('crm');

        $factory = Container::getInstance()->getFactory($entityTypeId);

        if (!$factory) {
            throw new \RuntimeException('Не найдена фабрика смарт-процесса: ' . $entityTypeId);
        }

        $item = $factory->getItem($itemId);

        if (!$item) {
            throw new \RuntimeException('Не найдена заявка на закупку: ' . $itemId);
        }

        foreach ($fields as $fieldName => $value) {
            if ($fieldName === 'STAGE_ID') {
                $item->setStageId((string)$value);
                continue;
            }

            $item->set($fieldName, $value);
        }

        $operation = $factory->getUpdateOperation($item);

        if (method_exists($operation, 'disableCheckAccess')) {
            $operation->disableCheckAccess();
        }

        if (method_exists($operation, 'disableCheckFieldsCheck')) {
            $operation->disableCheckFieldsCheck();
        }

        $result = $operation->launch();

        if (!$result->isSuccess()) {
            if (isset($fields['STAGE_ID'])) {
                $this->forceUpdateStageId($entityTypeId, $itemId, (string)$fields['STAGE_ID']);
                return;
            }

            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        /*
         * D7 мог вернуть успех, но фактически не изменить STAGE_ID.
         */
        if (isset($fields['STAGE_ID'])) {
            $expectedStageId = (string)$fields['STAGE_ID'];
            $updatedItem = $factory->getItem($itemId);

            if (!$updatedItem) {
                throw new \RuntimeException('Не удалось перечитать заявку после обновления: ' . $itemId);
            }

            $realStageId = (string)$updatedItem->getStageId();

            if ($realStageId !== $expectedStageId) {
                $this->forceUpdateStageId($entityTypeId, $itemId, $expectedStageId);
            }
        }
    }

    /**
     * Возвращает запчасти заявки.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param array $itemData
     * @return array
     */
    private function getProductsForRequest(int $entityTypeId, int $itemId, array $itemData): array
    {
        $products = $this->getProductRows($entityTypeId, $itemId);

        if (!empty($products)) {
            return $products;
        }

        $productId = (int)($itemData['UF_SC_SOURCE_PRODUCT_ID'] ?? 0);
        $quantity = (float)($itemData['UF_SC_QUANTITY'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            return [];
        }

        return [
            [
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $quantity,
            ],
        ];
    }

    /**
     * Сохраняет товарные строки заявки.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param array $products
     * @return void
     */
    private function setProductRows(int $entityTypeId, int $itemId, array $products): void
    {
        if (!class_exists('\CCrmProductRow')) {
            return;
        }

        $ownerType = 'T' . $entityTypeId;
        $rows = [];

        foreach ($products as $product) {
            $productId = (int)($product['PRODUCT_ID'] ?? 0);
            $quantity = (float)($product['QUANTITY'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $rows[] = [
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $quantity,
            ];
        }

        if (!empty($rows)) {
            \CCrmProductRow::SaveRows($ownerType, $itemId, $rows);
        }
    }

    /**
     * Возвращает товарные строки заявки.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return array
     */
    private function getProductRows(int $entityTypeId, int $itemId): array
    {
        if (!class_exists('\CCrmProductRow')) {
            return [];
        }

        return \CCrmProductRow::LoadRows('T' . $entityTypeId, $itemId) ?: [];
    }

    /**
     * Возвращает ID стадии "Выполнено".
     *
     * @param int $entityTypeId
     * @return string
     */
    private function getDoneStageId(int $entityTypeId): string
    {
        $doneStageId = $this->findStageIdByName($entityTypeId, 'Выполнено');

        if ($doneStageId !== '') {
            return $doneStageId;
        }

        $doneStageId = ModuleSettings::getPurchaseDoneStageId();

        if ($doneStageId !== '') {
            return $doneStageId;
        }

        /*
         * Фактическая стадия "Выполнено" у твоего процесса.
         */
        if ($entityTypeId === 1046) {
            return 'DT1046_6:SUCCESS';
        }

        return '';
    }

    /**
     * Ищет ID стадии смарт-процесса по названию.
     *
     * @param int $entityTypeId
     * @param string $stageName
     * @return string
     */
    private function findStageIdByName(int $entityTypeId, string $stageName): string
    {
        global $DB;

        $entityTypeIdSql = $DB->ForSql((string)$entityTypeId);
        $stageNameSql = $DB->ForSql($stageName);

        $result = $DB->Query("
            SELECT STATUS_ID
            FROM b_crm_status
            WHERE NAME = '{$stageNameSql}'
              AND (
                    STATUS_ID LIKE 'DT{$entityTypeIdSql}_%'
                    OR ENTITY_ID LIKE '%{$entityTypeIdSql}%'
                    OR ENTITY_ID LIKE 'DYNAMIC_{$entityTypeIdSql}_STAGE_%'
              )
            ORDER BY SORT ASC
        ");

        $row = $result->Fetch();

        return $row ? (string)$row['STATUS_ID'] : '';
    }

    /**
     * Проверяет, обработана ли заявка.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return bool
     */
    private function isProcessed(int $entityTypeId, int $itemId): bool
    {
        return $this->getProcessedStatus($entityTypeId, $itemId) !== '';
    }

    /**
     * Возвращает статус обработки заявки.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return string
     */
    private function getProcessedStatus(int $entityTypeId, int $itemId): string
    {
        global $DB;

        $entityTypeId = (int)$entityTypeId;
        $itemId = (int)$itemId;

        $result = $DB->Query("
            SELECT STATUS
            FROM sharov_sc_purchase_processed
            WHERE ENTITY_TYPE_ID = {$entityTypeId}
              AND ITEM_ID = {$itemId}
            LIMIT 1
        ");

        $row = $result->Fetch();

        return $row ? (string)$row['STATUS'] : '';
    }

    /**
     * Помечает заявку как обработанную.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param string $status
     * @return void
     */
    private function markProcessed(int $entityTypeId, int $itemId, string $status): void
    {
        global $DB;

        $entityTypeId = (int)$entityTypeId;
        $itemId = (int)$itemId;
        $statusSql = $DB->ForSql($status);

        $DB->Query("
            INSERT INTO sharov_sc_purchase_processed
                (ENTITY_TYPE_ID, ITEM_ID, STATUS, DATE_CREATE)
            VALUES
                ({$entityTypeId}, {$itemId}, '{$statusSql}', NOW())
            ON DUPLICATE KEY UPDATE
                STATUS = '{$statusSql}',
                DATE_CREATE = NOW()
        ");
    }

    /**
     * Удаляет отметку обработки заявки.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return void
     */
    private function removeProcessed(int $entityTypeId, int $itemId): void
    {
        global $DB;

        $entityTypeId = (int)$entityTypeId;
        $itemId = (int)$itemId;

        $DB->Query("
            DELETE FROM sharov_sc_purchase_processed
            WHERE ENTITY_TYPE_ID = {$entityTypeId}
              AND ITEM_ID = {$itemId}
        ");
    }

    /**
     * Завершает заявку, если закупка уже была выполнена,
     * но стадия не успела перейти в Выполнено.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @return void
     */
    private function finishApprovedWithoutPurchase(int $entityTypeId, int $itemId): void
    {
        $doneStageId = $this->getDoneStageId($entityTypeId);

        if ($doneStageId === '') {
            throw new \RuntimeException('Не удалось найти стадию Выполнено');
        }

        $this->updateSmartProcessItem($entityTypeId, $itemId, [
            'STAGE_ID' => $doneStageId,
        ]);

        $this->markProcessed($entityTypeId, $itemId, 'APPROVED');
    }

    /**
     * Принудительно меняет стадию заявки.
     *
     * Используется только как fallback, если агент не смог сменить стадию через D7.
     *
     * @param int $entityTypeId
     * @param int $itemId
     * @param string $stageId
     * @return void
     */
    private function forceUpdateStageId(int $entityTypeId, int $itemId, string $stageId): void
    {
        global $DB;

        $entityTypeId = (int)$entityTypeId;
        $itemId = (int)$itemId;
        $stageIdSql = $DB->ForSql($stageId);

        $tableName = $this->getDynamicItemsTableName($entityTypeId);

        if ($tableName === '') {
            throw new \RuntimeException(
                'Не удалось определить таблицу элементов смарт-процесса entityTypeId=' . $entityTypeId
            );
        }

        $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

        $DB->Query("
            UPDATE {$safeTableName}
            SET STAGE_ID = '{$stageIdSql}'
            WHERE ID = {$itemId}
        ");

        $checkResult = $DB->Query("
            SELECT STAGE_ID
            FROM {$safeTableName}
            WHERE ID = {$itemId}
            LIMIT 1
        ");

        $row = $checkResult->Fetch();

        if (!$row) {
            throw new \RuntimeException(
                'Не удалось найти заявку в таблице ' . $safeTableName . ', ID=' . $itemId
            );
        }

        if ((string)$row['STAGE_ID'] !== $stageId) {
            throw new \RuntimeException(
                'Не удалось принудительно сменить стадию. Ожидали: '
                . $stageId
                . ', фактически: '
                . (string)$row['STAGE_ID']
            );
        }
    }

    /**
     * Определяет физическую таблицу элементов смарт-процесса.
     *
     * У Bitrix может быть:
     * - b_crm_dynamic_items_1046
     * - b_crm_dynamic_items_3
     * где 3 — ID типа в b_crm_dynamic_type.
     *
     * @param int $entityTypeId
     * @return string
     */
    private function getDynamicItemsTableName(int $entityTypeId): string
    {
        global $DB;

        $entityTypeId = (int)$entityTypeId;

        $candidates = [
            'b_crm_dynamic_items_' . $entityTypeId,
        ];

        $typeResult = $DB->Query("
            SELECT ID
            FROM b_crm_dynamic_type
            WHERE ENTITY_TYPE_ID = {$entityTypeId}
            LIMIT 1
        ");

        $type = $typeResult->Fetch();

        if ($type && (int)$type['ID'] > 0) {
            $candidates[] = 'b_crm_dynamic_items_' . (int)$type['ID'];
        }

        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $tableName) {
            $tableNameSql = $DB->ForSql($tableName);

            $tableResult = $DB->Query("
                SHOW TABLES LIKE '{$tableNameSql}'
            ");

            if ($tableResult->Fetch()) {
                return $tableName;
            }
        }

        return '';
    }
}