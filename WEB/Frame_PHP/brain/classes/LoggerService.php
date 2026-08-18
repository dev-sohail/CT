<?php
namespace Services;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;

class LoggerService
{
    private static ?MonologLogger $instance = null;
    private MonologLogger $logger;

    public function __construct(string $name = 'app', ?string $logPath = null)
    {
        $this->logger = new MonologLogger($name);

        $dir = $logPath ?? (APP_LOGS ?? (APP_STORAGE . '/logs'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/' . $name . '.log';

        $rotating = new RotatingFileHandler($file, 14, Level::Info, true, 0644);
        $rotating->setFormatter(new JsonFormatter(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->logger->pushHandler($rotating);

        $stream = new StreamHandler($file, Level::Info);
        $this->logger->pushHandler($stream);
    }

    public static function get(string $channel = 'app'): MonologLogger
    {
        if (self::$instance === null) {
            self::$instance = (new self($channel))->logger;
        }
        return self::$instance;
    }

    public static function init(string $channel = 'app', ?string $path = null): void
    {
        if (self::$instance === null) {
            self::$instance = (new self($channel, $path))->logger;
        }
    }

    public static function log(
        string $message,
        array $context = [],
        string $level = 'INFO',
        ?string $category = null
    ): void {
        self::get();
        $monologLevel = match (strtoupper($level)) {
            'DEBUG'    => Level::Debug,
            'INFO'     => Level::Info,
            'NOTICE'   => Level::Notice,
            'WARNING'  => Level::Warning,
            'ERROR'    => Level::Error,
            'CRITICAL' => Level::Critical,
            'ALERT'    => Level::Alert,
            default    => Level::Info,
        };

        $extra = [
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
            'user'     => $_SESSION['user'] ?? '',
            'uri'      => $_SERVER['REQUEST_URI'] ?? '',
            'category' => $category ?? 'general',
        ];

        self::$instance->log($monologLevel, $message, array_merge($context, $extra));
    }
}
