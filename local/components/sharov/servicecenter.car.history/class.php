<?php

use Bitrix\Crm\ContactTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sharov\ServiceCenter\Service\CarHistoryService;
use Sharov\ServiceCenter\Service\CarService;

class ServiceCenterCarHistoryComponent extends CBitrixComponent
{
    /**
     * Запускает компонент истории автомобиля.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        Loader::includeModule('crm');
        Loader::includeModule('sharov.servicecenter');

        Loc::loadMessages(__FILE__);

        $carId = (int)($this->arParams['CAR_ID'] ?? 0);
        $carService = new CarService();
        $historyService = new CarHistoryService();

        $car = $carId > 0 ? $carService->getById($carId) : null;
        $contact = $car ? $this->getContact((int)$car['CONTACT_ID']) : null;

        $this->arResult = [
            'CAR_ID' => $carId,
            'CAR' => $car,
            'CONTACT' => $contact,
            'TITLE' => $this->buildTitle($car, $contact),
            'DEALS' => $car ? $historyService->getHistoryByCarId($carId) : [],
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает контакт владельца автомобиля.
     *
     * @param int $contactId
     * @return array|null
     */
    private function getContact(int $contactId): ?array
    {
        if ($contactId <= 0) {
            return null;
        }

        $contact = ContactTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'FULL_NAME'],
            'filter' => ['=ID' => $contactId],
            'limit' => 1,
        ])->fetch();

        return $contact ?: null;
    }

    /**
     * Формирует заголовок окна истории.
     *
     * @param array|null $car
     * @param array|null $contact
     * @return string
     */
    private function buildTitle(?array $car, ?array $contact): string
    {
        if (!$car) {
            return Loc::getMessage('SHAROV_SC_HISTORY_TITLE_EMPTY') ?: 'История автомобиля';
        }

        $carName = trim((string)$car['BRAND'] . ' ' . (string)$car['MODEL']);
        $licensePlate = (string)$car['LICENSE_PLATE'];
        $contactName = $this->getContactName($contact);

        return sprintf('%s - %s (%s)', $carName, $licensePlate, $contactName);
    }

    /**
     * Возвращает отображаемое имя контакта.
     *
     * @param array|null $contact
     * @return string
     */
    private function getContactName(?array $contact): string
    {
        if (!$contact) {
            return Loc::getMessage('SHAROV_SC_HISTORY_UNKNOWN_CLIENT') ?: 'Клиент не найден';
        }

        if (!empty($contact['FULL_NAME'])) {
            return (string)$contact['FULL_NAME'];
        }

        $parts = array_filter([
            $contact['LAST_NAME'] ?? '',
            $contact['NAME'] ?? '',
            $contact['SECOND_NAME'] ?? '',
        ]);

        return trim(implode(' ', $parts)) ?: ('ID ' . (int)$contact['ID']);
    }
}
