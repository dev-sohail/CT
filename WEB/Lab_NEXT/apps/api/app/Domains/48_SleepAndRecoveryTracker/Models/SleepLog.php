<?php

namespace App\Domains\SleepAndRecoveryTracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SleepLog extends Model
{
    protected $fillable = ['user_id', 'sleep_date', 'bedtime', 'wake_time', 'duration_minutes', 'quality', 'interruptions', 'deep_minutes', 'light_minutes', 'rem_minutes', 'notes', 'metadata'];
    protected $casts = ['sleep_date' => 'date', 'bedtime' => 'datetime', 'wake_time' => 'datetime', 'metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
