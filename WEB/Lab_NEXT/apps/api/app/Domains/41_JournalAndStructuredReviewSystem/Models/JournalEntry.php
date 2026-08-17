<?php

namespace App\Domains\JournalAndStructuredReviewSystem\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    protected $fillable = [
        'user_id', 'entry_date', 'title', 'body', 'mood', 'energy',
        'gratitude', 'wins', 'lessons', 'tags', 'is_private', 'metadata',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'mood' => 'integer',
        'energy' => 'integer',
        'gratitude' => 'array',
        'wins' => 'array',
        'lessons' => 'array',
        'tags' => 'array',
        'is_private' => 'boolean',
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
        return $query->where(fn (Builder $q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%"));
    }
}
