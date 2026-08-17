<?php

namespace App\Domains\TimeAuditAndTimeBlockAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TimeEntry extends Model
{
    protected $table = 'time_entries';

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'activity',
        'category',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function durationMinutes(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }
        return (int) $this->started_at->diffInMinutes($this->ended_at);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}