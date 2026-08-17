<?php

declare(strict_types=1);

namespace Ctlab\Support;

use Illuminate\Support\Facades\DB;
use Ctlab\Support\Contracts\ActionContract;

abstract class Action implements ActionContract
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
}