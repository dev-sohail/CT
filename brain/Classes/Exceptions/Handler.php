<?php
/**
 * Class Handler
 *
 * A simple generic handler that can register and execute callbacks for different event types.
 */
class Handler
{
    protected array $events = [];

    /**
     * Register a callback for a specific event.
     */
    public function on(string $event, callable $callback): void
    {
        $this->events[$event][] = $callback;
    }

    /**
     * Trigger all callbacks for a specific event.
     */
    public function trigger(string $event, ...$args): void
    {
        if (!empty($this->events[$event])) {
            foreach ($this->events[$event] as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Remove all callbacks for a specific event.
     */
    public function off(string $event): void
    {
        unset($this->events[$event]);
    }
}
