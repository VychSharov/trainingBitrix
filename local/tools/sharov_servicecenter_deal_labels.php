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

    if (!Loader::includeModule('crm')) {
        throw new RuntimeException('Модуль CRM не подключен');
    }

    if (!Loader::includeModule('sharov.servicecenter')) {
        throw new RuntimeException('Модуль sharov.servicecenter не подключен');
    }

    $dealId = (int)(
        $_REQUEST['dealId']
        ?? $_REQUEST['deal_id']
        ?? $_REQUEST['DEAL_ID']
        ?? $_REQUEST['id']
        ?? 0
    );

    if ($dealId <= 0) {
        throw new RuntimeException('Не указан ID сделки');
    }

    $dealResult = CCrmDeal::GetListEx(
        [],
        [
            '=ID' => $dealId,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        false,
        [
            'ID',
            'TITLE',
            'UF_CRM_SC_CAR_ID',
            'UF_CRM_SC_MECHANIC_ID',
        ]
    );

    $deal = $dealResult->Fetch();

    if (!$deal) {
        throw new RuntimeException('Сделка не найдена');
    }

    $carId = (int)($deal['UF_CRM_SC_CAR_ID'] ?? 0);
    $mechanicId = (int)($deal['UF_CRM_SC_MECHANIC_ID'] ?? 0);

    $carLabel = '';
    $mechanicLabel = '';

    if ($carId > 0) {
        $carService = new CarService();
        $car = $carService->getById($carId);

        if ($car) {
            $carLabel = trim(
                $car['BRAND']
                . ' '
                . $car['MODEL']
                . ' — '
                . $car['LICENSE_PLATE']
            );

            if (!empty($car['YEAR'])) {
                $carLabel .= ', ' . $car['YEAR'];
            }

            if (!empty($car['COLOR'])) {
                $carLabel .= ', ' . $car['COLOR'];
            }
        }
    }

    if ($mechanicId > 0) {
        $userResult = CUser::GetByID($mechanicId);
        $user = $userResult->Fetch();

        if ($user) {
            $mechanicLabel = trim(
                $user['LAST_NAME']
                . ' '
                . $user['NAME']
                . ' '
                . $user['SECOND_NAME']
            );

            if ($mechanicLabel === '') {
                $mechanicLabel = $user['LOGIN'];
            }
        }
    }

    echo Json::encode([
        'success' => true,
        'dealId' => $dealId,
        'carId' => $carId,
        'carLabel' => $carLabel,
        'mechanicId' => $mechanicId,
        'mechanicLabel' => $mechanicLabel,
    ]);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}