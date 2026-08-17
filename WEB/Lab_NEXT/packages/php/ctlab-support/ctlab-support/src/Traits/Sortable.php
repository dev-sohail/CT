<?php

declare(strict_types=1);

namespace Ctlab\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds query sorting support to Eloquent models.
 *
 * Override `$sortable` to define which columns can be sorted.
 *
 * Usage:
 *     class Post extends Model
 *     {
 *         use Sortable;
 *         protected array $sortable = ['created_at', 'title'];
 *     }
 *
 *     Post::sort('created_at', 'desc')->get();
 */
trait Sortable
{
    /**
     * Columns that can be sorted. Override in subclasses.
     *
     * @var list<string>
     */
    protected array $sortable = ['id', 'created_at', 'updated_at'];

    /**
     * Default sort column.
     */
    protected string $defaultSort = 'id';

    /**
     * Default sort direction.
     */
    protected string $defaultDirection = 'desc';

    public function scopeSort(Builder $query, ?string $column = null, string $direction = 'asc'): Builder
    {
        $column = $column ?? $this->defaultSort;
        $direction = in_array(strtolower($direction), ['asc', 'desc'], true) ? strtolower($direction) : $this->defaultDirection;

        if (in_array($column, $this->sortable, true)) {
            $query->orderBy($column, $direction);
        } else {
            $query->orderBy($this->defaultSort, $this->defaultDirection);
        }

        return $query;
    }
}
