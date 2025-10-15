<?php

declare(strict_types=1);

class Middleware
{
    protected array $stack = [];
    protected array $globalMiddleware = [];

    public function add(callable|string $middleware): self
    {
        $this->stack[] = $middleware;
        return $this;
    }

    public function global(callable|string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function handle(mixed $payload, ?callable $destination = null): mixed
    {
        $middleware = array_merge($this->globalMiddleware, $this->stack);
        
        $runner = array_reduce(
            array_reverse($middleware),
            fn($next, $mw) => fn($p) => $this->execute($mw, $p, $next),
            $destination ?? fn($p) => $p
        );

        return $runner($payload);
    }

    protected function execute(callable|string $middleware, mixed $payload, callable $next): mixed
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new RuntimeException("Middleware class not found: $middleware");
            }
            $middleware = new $middleware();
            if (!method_exists($middleware, 'handle')) {
                throw new RuntimeException("Middleware must have a handle method");
            }
            return $middleware->handle($payload, $next);
        }

        return $middleware($payload, $next);
    }

    public function clear(): void
    {
        $this->stack = [];
    }

    public function clearGlobal(): void
    {
        $this->globalMiddleware = [];
    }

    public function getStack(): array
    {
        return $this->stack;
    }
}
