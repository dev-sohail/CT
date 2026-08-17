<?php

declare(strict_types=1);

namespace Ctlab\Support\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Ctlab\Support\Repositories\Repository;

/**
 * Base service for domain modules.
 *
 * Services orchestrate business logic and transaction boundaries.
 * They depend on a Repository (injected via constructor) and never
 * touch Eloquent directly — all data access goes through the repository.
 *
 * @template TRepository of Repository
 */
abstract class Service
{
    public function __construct(
        protected readonly Repository $repository,
    ) {}

    /**
     * Get the underlying repository.
     */
    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * Execute a callback inside a database transaction.
     *
     * If the callback returns a value, that value is returned from
     * the transaction. If the callback throws, the transaction is
     * rolled back and the exception propagates.
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

    /**
     * Get the database connection for raw queries (use sparingly).
     */
    protected function connection(): ConnectionInterface
    {
        return DB::connection();
    }
}
