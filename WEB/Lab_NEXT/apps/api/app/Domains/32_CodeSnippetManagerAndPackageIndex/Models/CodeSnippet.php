<?php

namespace App\Domains\CodeSnippetManagerAndPackageIndex\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CodeSnippet extends Model
{
    protected $table = 'code_snippets';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'language',
        'code',
        'tags',
        'package_type',
        'package_name',
        'package_version',
        'favorite',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'favorite' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }
}