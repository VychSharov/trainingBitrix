<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Localization\Loc;
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/otus/debug.php');

$logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log';

$dir = dirname($logPath);
if (!is_dir($dir))
{
    mkdir($dir, 0755, true);
}

file_put_contents($logPath, '', LOCK_EX);

file_put_contents(
    $logPath,
    'OTUS ' . (Loc::getMessage('LOG_CLEAR') ?: 'Лог очищен') . ': ' . date('Y-m-d H:i:s') . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

LocalRedirect('/otus/students_dz/homework2/');
