<?php

declare(strict_types=1);

namespace Ctlab\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds query filtering support to Eloquent models.
 *
 * Override `$filterable` to define which columns can be filtered.
 *
 * Usage:
 *     class Post extends Model
 *     {
 *         use Filterable;
 *         protected array $filterable = ['status', 'author_id'];
 *     }
 *
 *     Post::filter(['status' => 'published'])->get();
 */
trait Filterable
{
    /**
     * Columns that can be filtered. Override in subclasses.
     *
     * @var list<string>
     */
    protected array $filterable = [];

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($column, $this->filterable, true)) {
                $query->where($column, $value);
            }
        }

        return $query;
    }
}
