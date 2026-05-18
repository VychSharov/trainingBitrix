<?php

namespace Sharov\ServiceCenter\Infrastructure;

class Logger
{
    private const LOG_FILE = '/local/logs/servicecenter.log';

    /**
     * Пишет ошибку в лог.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Пишет информационное сообщение в лог.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE;
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $file,
            sprintf(
                "[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                $level,
                $message,
                json_encode($context, JSON_UNESCAPED_UNICODE)
            ),
            FILE_APPEND
        );
    }
}