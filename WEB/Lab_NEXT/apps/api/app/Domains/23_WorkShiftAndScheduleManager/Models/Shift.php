<?php

namespace App\Domains\WorkShiftAndScheduleManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'user_id',
        'title',
        'starts_at',
        'ends_at',
        'location',
        'client',
        'hourly_rate',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'hourly_rate' => 'float',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function durationHours(): float
    {
        return round($this->starts_at->diffInMinutes($this->ends_at) / 60, 2);
    }

    public function estimatedEarnings(): ?float
    {
        if ($this->hourly_rate === null) {
            return null;
        }
        return round($this->durationHours() * $this->hourly_rate, 2);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function overlapsWith(Shift $other): bool
    {
        return $this->starts_at->lt($other->ends_at) && $other->starts_at->lt($this->ends_at);
    }

    /**
     * Find conflicting shifts for this shift (excluding itself).
     */
    public function conflicts(): Builder
    {
        return Shift::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'scheduled')
            ->where(function (Builder $q) {
                $q->whereBetween('starts_at', [$this->starts_at, $this->ends_at])
                    ->orWhereBetween('ends_at', [$this->starts_at, $this->ends_at])
                    ->orWhere(function (Builder $inner) {
                        $inner->where('starts_at', '<=', $this->starts_at)
                            ->where('ends_at', '>=', $this->ends_at);
                    });
            });
    }
}