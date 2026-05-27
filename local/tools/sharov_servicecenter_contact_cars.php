<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Sharov\ServiceCenter\Service\CarService;

header('Content-Type: application/json; charset=UTF-8');

try {
    global $USER;

    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    if (!Loader::includeModule('sharov.servicecenter')) {
        throw new RuntimeException('Модуль sharov.servicecenter не подключен');
    }

    $contactId = (int)(
        $_REQUEST['contactId']
        ?? $_REQUEST['contact_id']
        ?? $_REQUEST['CONTACT_ID']
        ?? 0
    );

    if ($contactId <= 0) {
        throw new RuntimeException('Не указан клиент');
    }

    $carService = new CarService();
    $cars = $carService->getListByContactId($contactId);

    $items = [];

    foreach ($cars as $car) {
        $label = trim(
            $car['BRAND']
            . ' '
            . $car['MODEL']
            . ' — '
            . $car['LICENSE_PLATE']
        );

        if (!empty($car['YEAR'])) {
            $label .= ', ' . $car['YEAR'];
        }

        if (!empty($car['COLOR'])) {
            $label .= ', ' . $car['COLOR'];
        }

        $items[] = [
            'id' => (int)$car['ID'],
            'label' => $label,
        ];
    }

    echo Json::encode([
        'success' => true,
        'contactId' => $contactId,
        'cars' => $items,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}