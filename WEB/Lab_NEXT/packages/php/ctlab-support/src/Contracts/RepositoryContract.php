<?php

namespace Ctlab\Support\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryContract
{
    public function all(array $columns = ['*']): Collection;

    public function find(mixed $id, array $columns = ['*']): ?Model;

    public function findOrFail(mixed $id, array $columns = ['*']): Model;

    public function create(array $attributes): Model;

    public function update(mixed $id, array $attributes): Model;

    public function delete(mixed $id): bool;

    public function paginate(int $perPage = 15, array $columns = ['*']);

    public function query(): Builder;
}
