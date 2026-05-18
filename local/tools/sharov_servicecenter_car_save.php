<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Sharov\ServiceCenter\Service\CarService;

header('Content-Type: application/json; charset=UTF-8');

try {
    if (!check_bitrix_sessid()) {
        throw new RuntimeException('Неверная сессия');
    }

    if (!Loader::includeModule('sharov.servicecenter')) {
        throw new RuntimeException('Модуль sharov.servicecenter не подключен');
    }

    $contactId = (int)($_POST['contactId'] ?? 0);
    $brand = trim((string)($_POST['brand'] ?? ''));
    $model = trim((string)($_POST['model'] ?? ''));
    $licensePlate = trim((string)($_POST['licensePlate'] ?? ''));
    $year = (int)($_POST['year'] ?? 0);
    $color = trim((string)($_POST['color'] ?? ''));
    $mileage = (int)($_POST['mileage'] ?? 0);
    $vin = trim((string)($_POST['vin'] ?? ''));

    if ($contactId <= 0) {
        throw new RuntimeException('Не указан контакт');
    }

    if ($brand === '') {
        throw new RuntimeException('Укажите марку автомобиля');
    }

    if ($model === '') {
        throw new RuntimeException('Укажите модель автомобиля');
    }

    if ($licensePlate === '') {
        throw new RuntimeException('Укажите номер автомобиля');
    }

    $carService = new CarService();

    $carId = $carService->add([
        'CONTACT_ID' => $contactId,
        'BRAND' => $brand,
        'MODEL' => $model,
        'LICENSE_PLATE' => $licensePlate,
        'YEAR' => $year > 0 ? $year : null,
        'COLOR' => $color,
        'MILEAGE' => $mileage > 0 ? $mileage : null,
        'VIN' => $vin,
    ]);

    echo Json::encode([
        'success' => true,
        'carId' => $carId,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}