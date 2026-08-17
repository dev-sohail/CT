<?php

declare(strict_types=1);

namespace Ctlab\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

abstract class Service
{
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

    /**
     * Get the default database connection for raw queries.
     */
    protected function connection(): ConnectionInterface
    {
        return DB::connection();
    }
}