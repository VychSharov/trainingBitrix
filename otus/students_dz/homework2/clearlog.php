<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Localization\Loc;
Loc::loadLanguageFile(
    $_SERVER['DOCUMENT_ROOT'] . '/otus/lang/' . LANGUAGE_ID . '/debug.php'
);

require_once __DIR__ . '/logger.php';

$logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log_custom.log';

$logger = new Logger(filePath: $logPath);
$logger->clear();

LocalRedirect('/otus/students_dz/homework2/');
