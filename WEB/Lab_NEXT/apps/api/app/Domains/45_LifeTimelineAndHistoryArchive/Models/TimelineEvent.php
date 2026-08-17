<?php

namespace App\Domains\LifeTimelineAndHistoryArchive\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    protected $fillable = ['user_id', 'event_date', 'title', 'description', 'type', 'category', 'significance', 'location', 'people', 'tags', 'metadata'];
    protected $casts = ['event_date' => 'date', 'significance' => 'integer', 'people' => 'array', 'tags' => 'array', 'metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
