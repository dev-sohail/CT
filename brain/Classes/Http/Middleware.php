<?php
/**
 * Class Middleware
 *
 * A simple middleware handler for executing a stack of callbacks in sequence.
 */
class Middleware
{
    protected array $stack = [];

    /**
     * Add a middleware callback to the stack.
     */
    public function add(callable $callback): self
    {
        $this->stack[] = $callback;
        return $this;
    }

    /**
     * Execute the middleware stack.
     *
     * @param mixed $payload Data passed through the middleware chain.
     */
    public function handle($payload)
    {
        $next = function ($index, $payload) use (&$next) {
            if (isset($this->stack[$index])) {
                $middleware = $this->stack[$index];
                return $middleware($payload, function ($newPayload) use ($index, $next) {
                    return $next($index + 1, $newPayload);
                });
            }
            return $payload;
        };

        return $next(0, $payload);
    }
}
