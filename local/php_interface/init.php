<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(null, [
    'Otus\\Homework3\\AbstractPage' => '/local/php_interface/src/Otus/Homework3/AbstractPage.php',
    'Otus\\Homework3\\DoctorsPage'  => '/local/php_interface/src/Otus/Homework3/DoctorsPage.php',
    'Otus\\Homework3\\ProceduresPage' => '/local/php_interface/src/Otus/Homework3/ProceduresPage.php',
    'Local\Model\ORM\CarOfferTable' => '/local/php_interface/lib/Model/ORM/CarOfferTable.php',
    'Local\Model\Iblock\Cars'       => '/local/php_interface/lib/Model/Iblock/Cars.php',
    'Local\Model\Iblock\Dealers'    => '/local/php_interface/lib/Model/Iblock/Dealers.php',
]);

// === Sharov debug logger (safe) ===
if (!function_exists('sharov_debug_log')) {
    function sharov_debug_log(string $msg, array $ctx = []): void
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/sharov_fatal.log';
        $date = date('Y-m-d H:i:s');

        $line = '['.$date.'] ' . $msg;
        if (!empty($ctx)) {
            $line .= ' | ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND);
    }
}

// логируем вообще всё (в файл), на экран не выводим
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_error_handler(function ($severity, $message, $file, $line) {
    sharov_debug_log('PHP ERROR', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);
    return false; // пусть Bitrix тоже обработает
});

set_exception_handler(function (Throwable $e) {
    sharov_debug_log('UNCAUGHT EXCEPTION', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'trace' => $e->getTraceAsString(),
    ]);
});

register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (in_array($e['type'], $fatalTypes, true)) {
        sharov_debug_log('FATAL SHUTDOWN', [
            'type' => $e['type'],
            'message' => $e['message'],
            'file' => $e['file'],
            'line' => $e['line'],
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]);
    }
});

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/otus_doctor_booking_property.php';

AddEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    array('OtusDoctorBookingProperty', 'GetUserTypeDescription')
);

$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

if (!defined('ADMIN_SECTION') && preg_match('#^/services/lists/16/view/0/#', $requestUri)) {
    \CJSCore::Init(array('popup'));

    $asset = \Bitrix\Main\Page\Asset::getInstance();
    $asset->addJs('/local/js/otus/booking-selector/script.js');
    $asset->addJs('/local/js/otus/public-list-booking.js');

    $messages = array(
        'OTUS_HW7_POPUP_TITLE' => 'Создание бронирования',
        'OTUS_HW7_PATIENT_LABEL' => 'ФИО пациента',
        'OTUS_HW7_TIME_LABEL' => 'Время записи',
        'OTUS_HW7_CREATE_BUTTON' => 'Создать',
        'OTUS_HW7_CANCEL_BUTTON' => 'Отмена',
        'OTUS_HW7_REQUIRED_FIELDS' => 'Заполните все поля',
        'OTUS_HW7_UNKNOWN_ERROR' => 'Неизвестная ошибка',
    );

    $asset->addString(
        '<script>window.OtusBookingJsMessages = ' . \CUtil::PhpToJSObject($messages) . ';</script>'
    );
}

if (!defined('ADMIN_SECTION')) {
    \CJSCore::Init(array('popup'));

    $asset = \Bitrix\Main\Page\Asset::getInstance();
    $asset->addJs('/local/js/otus/workday-confirm.js');

    $messages = array(
        'OTUS_HW8_POPUP_TITLE' => 'Начало рабочего дня',
        'OTUS_HW8_POPUP_TEXT' => 'Подтвердите начало рабочего дня',
        'OTUS_HW8_START_BUTTON' => 'Начать рабочий день',
        'OTUS_HW8_CANCEL_BUTTON' => 'Отмена',
    );

    $asset->addString(
        '<script>window.OtusWorkdayJsMessages = ' . \CUtil::PhpToJSObject($messages) . ';</script>'
    );
}

use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;

AddEventHandler('main', 'OnProlog', function () {
    $request = Context::getCurrent()->getRequest();
    $uri = $request->getRequestUri();

    if (
        strpos($uri, '/crm/deal/details/') !== false
        || strpos($uri, '/crm/deal/edit/') !== false
        || strpos($uri, 'crm.deal.details') !== false
    ) {
        Asset::getInstance()->addJs('/local/js/sharov.servicecenter/deal-service-selects.js');
    }
});

AddEventHandler('main', 'OnProlog', function () {
    $request = Context::getCurrent()->getRequest();
    $uri = $request->getRequestUri();

    if (
        strpos($uri, '/crm/type/1046/details/') !== false
        || strpos($uri, '/crm/type/1046/edit/') !== false
        || strpos($uri, 'crm.type.item.details') !== false
    ) {
        Asset::getInstance()->addJs('/local/js/sharov.servicecenter/purchase-request-selects.js?v=1');
    }
});

AddEventHandler('main', 'OnEpilog', static function () {
    global $APPLICATION;

    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $page = $APPLICATION->GetCurPage(false);

    $isDealPage =
        preg_match('#/crm/deal/details/\d+/#', $requestUri)
        || preg_match('#/crm/deal/details/\d+/#', $page);

    if (!$isDealPage) {
        return;
    }

    \Bitrix\Main\Page\Asset::getInstance()->addJs(
        '/local/js/sharov.servicecenter/deal-product-parts-filter.js'
    );
});