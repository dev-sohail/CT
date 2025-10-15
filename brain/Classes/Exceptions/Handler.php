<?php

declare(strict_types=1);

class Handler
{
    protected static ?Handler $instance = null;
    protected array $handlers = [];
    protected array $listeners = [];
    protected bool $debug = false;

    public static function getInstance(): Handler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    public function handleException(Throwable $e): void
    {
        $this->log($e);
        $this->report($e);
        $this->render($e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleException(
                new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
            );
        }
    }

    public function reportable(string $exception, callable $callback): void
    {
        $this->handlers[$exception] = $callback;
    }

    public function listen(string $event, callable $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    protected function log(Throwable $e): void
    {
        error_log(sprintf(
            "[%s] %s in %s:%d",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    protected function report(Throwable $e): void
    {
        foreach ($this->handlers as $exception => $callback) {
            if ($e instanceof $exception) {
                $callback($e);
                return;
            }
        }
    }

    protected function render(Throwable $e): void
    {
        $this->trigger('exception.render', $e);

        if ($this->debug || (defined('APP_DEBUG') && APP_DEBUG)) {
            $this->renderDebug($e);
        } else {
            $this->renderProduction($e);
        }
    }

    protected function renderDebug(Throwable $e): void
    {
        http_response_code(500);
        echo "<h1>Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
        echo "<p>File: " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

    protected function renderProduction(Throwable $e): void
    {
        http_response_code(500);
        echo "<h1>Server Error</h1>";
        echo "<p>An error occurred. Please try again later.</p>";
    }

    protected function trigger(string $event, ...$args): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $callback) {
                $callback(...$args);
            }
        }
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
}
