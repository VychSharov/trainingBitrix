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

    $carId = (int)($_REQUEST['carId'] ?? 0);

    if ($carId <= 0) {
        throw new RuntimeException('Не указан автомобиль');
    }

    $carService = new CarService();
    $car = $carService->getById($carId);

    if (!$car) {
        throw new RuntimeException('Автомобиль не найден');
    }

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

    echo Json::encode([
        'success' => true,
        'id' => $carId,
        'label' => $label,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}