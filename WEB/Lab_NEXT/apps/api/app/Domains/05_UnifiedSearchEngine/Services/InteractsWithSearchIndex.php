<?php

namespace App\Domains\UnifiedSearchEngine\Services;

use App\Domains\UnifiedSearchEngine\Contracts\Searchable;

trait InteractsWithSearchIndex
{
    protected static function bootInteractsWithSearchIndex(): void
    {
        static::saved(function (Searchable $model) {
            app(SearchService::class)->sync($model);
        });

        static::deleted(function (Searchable $model) {
            app(SearchService::class)->remove($model);
        });

        static::restored(function (Searchable $model) {
            app(SearchService::class)->sync($model);
        });
    }
}
