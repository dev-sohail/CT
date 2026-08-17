<?php

declare(strict_types=1);

namespace Ctlab\Support\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base repository for domain modules.
 *
 * Provides a clean query abstraction over Eloquent without exposing
 * domain logic to framework details. Extend this per domain and
 * override `$model` to bind to a specific Eloquent model.
 *
 * @template TModel of Model
 */
abstract class Repository
{
    /**
     * The Eloquent model this repository queries.
     *
     * @var class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * Begin a query against the model.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->newQuery();
    }

    /**
     * Get all records, optionally filtered.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TModel>
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->newQuery();

        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Paginate records with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  int  $perPage
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery();

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Find a record by primary key.
     *
     * @param  int|string  $id
     * @return TModel|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    /**
     * Find a record by a unique field.
     *
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value): ?Model
    {
        return $this->newQuery()->where($column, $value)->first();
    }

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->newQuery()->create($attributes);
    }

    /**
     * Update an existing record.
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->fresh() ?? $model;
    }

    /**
     * Delete a record.
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Count records with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function count(array $filters = []): int
    {
        $query = $this->newQuery();

        $query = $this->applyFilters($query, $filters);

        return (int) $query->count();
    }

    /**
     * Check if a record exists with the given attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function exists(array $attributes): bool
    {
        return $this->newQuery()->where($attributes)->exists();
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Create a fresh query builder for the model.
     *
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        return $this->model()::query();
    }

    /**
     * Apply filters to the query. Override in subclasses for domain-specific
     * filter handling. The base implementation handles simple equality
     * filters for columns that exist on the model.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($column, $this->sortableColumns(), true)) {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Apply sorting from filters.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function applySorting(Builder $query, array $filters): Builder
    {
        $sort = $filters['sort'] ?? null;
        $direction = $filters['direction'] ?? 'asc';

        if ($sort && in_array($sort, $this->sortableColumns(), true)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Columns that can be sorted by. Override in subclasses.
     *
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }
}
