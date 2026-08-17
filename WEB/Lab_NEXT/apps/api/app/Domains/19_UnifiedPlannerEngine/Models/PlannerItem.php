<?php

namespace App\Domains\UnifiedPlannerEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PlannerItem extends Model
{
    protected $table = 'planner_items';

    protected $fillable = [
        'user_id',
        'parent_id',
        'scope',
        'title',
        'description',
        'due_at',
        'status',
        'priority',
        'recurrence_rule',
        'sort_order',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

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
        return $this->hasMany(self::class, 'parent_id');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueBetween(Builder $query, \Carbon\Carbon $from, \Carbon\Carbon $to): Builder
    {
        return $query->where(function (Builder $q) use ($from, $to) {
            $q->whereBetween('due_at', [$from, $to])
                ->orWhereNull('due_at');
        });
    }
}