<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);
header('Content-Type: application/json; charset=UTF-8');

if (!Loader::includeModule('iblock')) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_ERR_IBLOCK') ?: 'Модуль iblock не подключен',
    ));
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_BAD_REQUEST') ?: 'Некорректный запрос',
    ));
    die();
}

if ((string)($_POST['action'] ?? '') !== 'create_booking') {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_BAD_ACTION') ?: 'Неизвестное действие',
    ));
    die();
}

$doctorId = (int)($_POST['doctor_id'] ?? 0);
$procedureId = (int)($_POST['procedure_id'] ?? 0);
$patientFio = trim((string)($_POST['patient_fio'] ?? ''));
$bookingTimeRaw = trim((string)($_POST['booking_time'] ?? ''));

if ($doctorId <= 0 || $procedureId <= 0 || $patientFio === '' || $bookingTimeRaw === '') {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_REQUIRED_FIELDS') ?: 'Заполните все поля',
    ));
    die();
}

$dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $bookingTimeRaw);
if (!$dateTime) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_BAD_DATETIME') ?: 'Некорректный формат даты',
    ));
    die();
}

$bookingTime = $dateTime->format('d.m.Y H:i:s');

$bookingIblockId = 0;
$iblockRes = CIBlock::GetList(array(), array('CODE' => 'booking', 'CHECK_PERMISSIONS' => 'N'));
if ($iblock = $iblockRes->Fetch()) {
    $bookingIblockId = (int)$iblock['ID'];
}

if ($bookingIblockId <= 0) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_BOOKING_IBLOCK_NOT_FOUND') ?: 'Инфоблок бронирования не найден',
    ));
    die();
}

$busyRes = CIBlockElement::GetList(
    array(),
    array(
        'IBLOCK_ID' => $bookingIblockId,
        'ACTIVE' => 'Y',
        'PROPERTY_DOCTOR' => $doctorId,
        'PROPERTY_BOOKING_TIME' => $bookingTime,
    ),
    false,
    array('nTopCount' => 1),
    array('ID')
);

if ($busyRes->Fetch()) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => Loc::getMessage('OTUS_HW7_TIME_BUSY') ?: 'Это время уже занято',
    ));
    die();
}

$procedureName = 'Процедура #' . $procedureId;
$procedureRes = CIBlockElement::GetList(
    array(),
    array('ID' => $procedureId),
    false,
    array('nTopCount' => 1),
    array('ID', 'NAME')
);

if ($procedure = $procedureRes->Fetch()) {
    $procedureName = $procedure['NAME'];
}

$elementName = $patientFio . ' - ' . $procedureName . ' - ' . $bookingTime;

$element = new CIBlockElement();
$newElementId = $element->Add(array(
    'IBLOCK_ID' => $bookingIblockId,
    'NAME' => $elementName,
    'ACTIVE' => 'Y',
    'PROPERTY_VALUES' => array(
        'PATIENT_FIO' => $patientFio,
        'BOOKING_TIME' => $bookingTime,
        'PROCEDURE' => $procedureId,
        'DOCTOR' => $doctorId,
    ),
));

if (!$newElementId) {
    echo Json::encode(array(
        'status' => 'error',
        'message' => $element->LAST_ERROR ?: 'Ошибка создания бронирования',
    ));
    die();
}

echo Json::encode(array(
    'status' => 'success',
    'message' => Loc::getMessage('OTUS_HW7_SUCCESS') ?: 'Бронирование создано',
    'id' => (int)$newElementId,
));
die();