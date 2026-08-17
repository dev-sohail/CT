<?php

namespace App\Domains\StudyAndCourseTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    protected $table = 'courses';

    protected $fillable = [
        'user_id',
        'title',
        'subject',
        'url',
        'provider',
        'status',
        'hours_target',
        'hours_spent',
        'started_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'hours_target' => 'integer',
        'hours_spent' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function progressPercent(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }
        if ($this->hours_target) {
            return (int) round(min(100, ($this->hours_spent / $this->hours_target) * 100));
        }
        return 0;
    }
}