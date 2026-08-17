<?php

namespace App\Domains\WorkoutAndTrainingPlanner\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workout extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'scheduled_on', 'started_at', 'completed_at', 'duration_minutes', 'calories_burned', 'status', 'exercises', 'notes', 'metadata'];
    protected $casts = ['scheduled_on' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'exercises' => 'array', 'metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
