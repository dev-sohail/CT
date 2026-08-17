<?php

namespace App\Domains\GoalHierarchyAndOKRTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Goal extends Model
{
    protected $table = 'goals';

    protected $fillable = [
        'user_id',
        'parent_id',
        'title',
        'description',
        'level',
        'start_at',
        'target_at',
        'status',
        'progress',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'target_at' => 'datetime',
        'progress' => 'float',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class, 'goal_id')->orderBy('sort_order');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'paused']);
    }

    /**
     * Recompute progress as the average of child goals and key results.
     */
    public function rollupProgress(): float
    {
        $values = [];

        foreach ($this->keyResults()->get() as $kr) {
            $values[] = $kr->computeProgress();
        }
        foreach ($this->children()->get() as $child) {
            $values[] = $child->rollupProgress();
        }

        $progress = $values === [] ? (float) $this->progress : round(array_sum($values) / count($values), 2);

        $this->forceFill(['progress' => $progress])->saveQuietly();

        return $progress;
    }
}