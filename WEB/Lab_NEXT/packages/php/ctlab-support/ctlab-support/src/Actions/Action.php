<?php

declare(strict_types=1);

namespace Ctlab\Support\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Base action for domain modules.
 *
 * Actions are single-purpose command handlers. Each action performs
 * exactly one use case (e.g., "Register User", "Create Invoice").
 * They are invokable — call them like `$action($dto)`.
 *
 * Actions may wrap their logic in a transaction via `transaction()`.
 * They never call other actions directly — that's the service's job.
 */
abstract class Action
{
    /**
     * Execute the action.
     *
     * Override this in subclasses. The base implementation throws
     * so subclasses are forced to define their own behavior.
     *
     * @param  array<string, mixed>|object  $input
     * @return mixed
     */
    abstract public function handle(mixed $input): mixed;

    /**
     * Make the action invokable: `$action($input)`.
     *
     * @param  array<string, mixed>|object  $input
     * @return mixed
     */
    public function __invoke(mixed $input): mixed
    {
        return $this->handle($input);
    }

    /**
     * Execute a callback inside a database transaction.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
