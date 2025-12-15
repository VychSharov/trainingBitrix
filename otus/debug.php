<?php
declare(strict_types=1);

use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

$logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log_custom.log';

$dir = dirname($logPath);
if (!is_dir($dir))
{
    mkdir($dir, 0755, true);
}

file_put_contents($logPath, 'OTUS debug.php ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND | LOCK_EX);

header('Content-Type: text/plain; charset=UTF-8');
echo "OK\n";