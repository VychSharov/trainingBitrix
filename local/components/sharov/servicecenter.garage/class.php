<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sharov\ServiceCenter\Service\CarService;

class ServiceCenterGarageComponent extends CBitrixComponent
{
    /**
     * Запуск компонента.
     *
     * @return void
     */
    public function executeComponent(): void
    {
        Loader::includeModule('sharov.servicecenter');
        Loc::loadMessages(__FILE__);

        $contactId = (int)($this->arParams['CONTACT_ID'] ?? 0);
        $carService = new CarService();

        $this->arResult = [
            'CONTACT_ID' => $contactId,
            'CARS' => $contactId > 0 ? $carService->getListByContactId($contactId) : [],
        ];

        $this->includeComponentTemplate();
    }
}
