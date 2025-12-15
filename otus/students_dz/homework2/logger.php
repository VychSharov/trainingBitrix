<?php
declare(strict_types=1);
class Logger
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function write(string $message): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) 
        {
            mkdir($dir, 0755, true);
        }

        $line = 'OTUS ' . $message . PHP_EOL;
        
        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
        
    }

    public function clear(): void
    {
        file_put_contents($this->filePath, '');
    }
}
