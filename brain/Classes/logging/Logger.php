<?php

declare(strict_types=1);

class Logger
{
    protected string $logPath;
    protected string $defaultFile = 'frame.log';
    protected bool $splitByLevel = true;
    protected static ?Logger $instance = null;

    public function __construct(string $logFile = 'frame.log', bool $splitByLevel = true)
    {
        $root = defined('ROOT') ? ROOT : __DIR__ . '/../../..';
        $this->logPath = defined('DIR_STORAGE_LOGS') ? DIR_STORAGE_LOGS : $root . '/Storage/logs';
        $this->defaultFile = $logFile;
        $this->splitByLevel = $splitByLevel;

        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0755, true);
        }
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        $timestamp = date('Y-m-d H:i:s');
        $interpolated = $this->interpolate($message, $context);
        $logLine = "[{$timestamp}] [{$level}] {$interpolated}" . PHP_EOL;

        $file = $this->getLogFile($level);
        @file_put_contents($file, $logLine, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    protected function getLogFile(string $level = ''): string
    {
        $date = date('Y-m-d');
        
        if ($this->splitByLevel && $level) {
            return $this->logPath . "/{$date}-" . strtolower($level) . '.log';
        }

        return $this->logPath . "/{$date}-{$this->defaultFile}";
    }

    protected function interpolate(string $message, array $context): string
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = is_scalar($val) ? (string)$val : json_encode($val);
        }

        return strtr($message, $replace);
    }

    public function clear(?string $level = null): bool
    {
        $date = date('Y-m-d');
        $file = $level 
            ? $this->logPath . "/{$date}-" . strtolower($level) . '.log'
            : $this->logPath . "/{$date}-{$this->defaultFile}";

        return file_exists($file) ? unlink($file) : false;
    }
}
