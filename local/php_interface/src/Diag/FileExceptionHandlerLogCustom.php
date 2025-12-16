<?php
declare(strict_types=1);

namespace Diag;

use Bitrix\Main;
use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\FileExceptionHandlerLog;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Localization\Loc;

class FileExceptionHandlerLogCustom extends FileExceptionHandlerLog
{
    /** @var int|null */
    private $level = null;

    public function initialize(array $options): void
    {
        Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/otus/debug.php');

        $logFile = '/local/logs/exceptions.log';
        if (!empty($options['file']))
        {
            $logFile = (string)$options['file'];
        }

        if ($logFile[0] === '/')
        {
            $logFile = Main\Application::getDocumentRoot() . $logFile;
        }

        $maxLogSize = !empty($options['log_size']) ? (int)$options['log_size'] : 1000000;

        if (isset($options['level']))
        {
            $this->level = (int)$options['level'];
        }

        $this->logger = new FileLogger($logFile, $maxLogSize);
    }

    public function write($exception, $logType): void
    {
        $text = Loc::getMessage('EXCEPTION_TEST') ?: 'тестовое исключение';

        $formatted = ExceptionHandlerFormatter::format($exception, false, $this->level);

        $lines = preg_split('/\r?\n/', trim($formatted));
        $clean = [];

        if (isset($lines[0])) {
            $clean[] = $lines[0]; 
        }
        if (isset($lines[1])) {
            $clean[] = $lines[1];
        }

        $formattedClean = implode("\n", $clean);

        $date = date('Y-m-d H:i:s');
 
        $logLevel = static::logTypeToLevel($logType);

        $message = "OTUS - {$date}  - {$text}\n{$formattedClean}\n";

        $this->logger->log($logLevel, $message, [
       
        ]);
    }
}
