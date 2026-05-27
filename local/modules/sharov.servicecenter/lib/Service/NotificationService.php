<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Localization\Loc;

class NotificationService
{
    /**
     * Уведомляет об автозакупке.
     *
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @return void
     */
    public function notifyAutoPurchaseDone(int $userId, int $productId, int $quantity): void
    {
        Loc::loadMessages(__FILE__);

        $message = Loc::getMessage('SHAROV_SC_NOTIFY_AUTO_PURCHASE_DONE', [
            '#PRODUCT_ID#' => $productId,
            '#QUANTITY#' => $quantity,
        ]) ?: sprintf('Запчасть ID %d закончилась. Автоматически закуплено %d шт.', $productId, $quantity);

        $this->send($userId, $message);
    }

    /**
     * Уведомляет о дубле открытого заказ-наряда.
     *
     * @param int $userId
     * @param array $deal
     * @return void
     */
    public function notifyDuplicateOpenDeal(int $userId, array $deal): void
    {
        Loc::loadMessages(__FILE__);

        if ($userId <= 0) {
            return;
        }

        $message = Loc::getMessage('SHAROV_SC_NOTIFY_DUPLICATE_OPEN_DEAL', [
            '#DEAL_TITLE#' => (string)($deal['TITLE'] ?? ''),
        ]) ?: ('По автомобилю уже есть открытая сделка: ' . (string)($deal['TITLE'] ?? ''));

        $this->send($userId, $message);
    }

    /**
     * Уведомляет инициатора об успешной закупке.
     *
     * @param int $userId
     * @param int $requestId
     * @return void
     */
    public function notifyPurchaseApproved(int $userId, int $requestId): void
    {
        Loc::loadMessages(__FILE__);

        $message = Loc::getMessage('SHAROV_SC_NOTIFY_PURCHASE_APPROVED', [
            '#REQUEST_ID#' => $requestId,
        ]) ?: ('Заявка на закупку №' . $requestId . ' успешно выполнена');

        $this->send($userId, $message);
    }

    /**
     * Уведомляет инициатора об отказе в закупке.
     *
     * @param int $userId
     * @param int $requestId
     * @param string $reason
     * @return void
     */
    public function notifyPurchaseRejected(int $userId, int $requestId, string $reason): void
    {
        Loc::loadMessages(__FILE__);

        $message = Loc::getMessage('SHAROV_SC_NOTIFY_PURCHASE_REJECTED', [
            '#REQUEST_ID#' => $requestId,
            '#REASON#' => $reason,
        ]) ?: ('Заявка на закупку №' . $requestId . ' отклонена. Причина: ' . $reason);

        $this->send($userId, $message);
    }

    /**
     * Отправляет внутреннее уведомление.
     *
     * @param int $userId
     * @param string $message
     * @return void
     */
    private function send(int $userId, string $message): void
    {
        if ($userId <= 0 || !class_exists('\CIMNotify')) {
            return;
        }

        \CIMNotify::Add([
            'TO_USER_ID' => $userId,
            'FROM_USER_ID' => 0,
            'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
            'NOTIFY_MODULE' => 'sharov.servicecenter',
            'NOTIFY_MESSAGE' => $message,
        ]);
    }
}
