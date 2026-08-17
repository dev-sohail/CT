<?php

namespace Ctlab\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Ctlab\Support\Contracts\RepositoryContract;

abstract class Repository implements RepositoryContract
{
    abstract protected function model(): string;

    protected function newModel(): Model
    {
        $model = $this->model();

        return new $model;
    }

    protected function newQuery(): Builder
    {
        $model = $this->model();

        return $model::query();
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->newQuery()->get($columns);
    }

    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        return $this->newQuery()->find($id, $columns);
    }

    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        return $this->newQuery()->findOrFail($id, $columns);
    }

    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    public function update(mixed $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);
        $model->update($attributes);

        return $model->fresh();
    }

    public function delete(mixed $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->newQuery()->paginate($perPage, $columns);
    }

    public function query(): Builder
    {
        return $this->newQuery();
    }
}
