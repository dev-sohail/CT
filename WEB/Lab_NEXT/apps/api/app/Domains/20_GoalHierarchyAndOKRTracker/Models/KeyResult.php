<?php

namespace App\Domains\GoalHierarchyAndOKRTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResult extends Model
{
    protected $table = 'key_results';

    protected $fillable = [
        'goal_id',
        'title',
        'current_value',
        'target_value',
        'unit',
        'status',
        'progress',
        'sort_order',
    ];

    protected $casts = [
        'current_value' => 'float',
        'target_value' => 'float',
        'progress' => 'float',
        'sort_order' => 'integer',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }

    /**
     * Progress = current/target clamped to 0-100.
     */
    public function computeProgress(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }
        return round(min(100, max(0, ($this->current_value / $this->target_value) * 100)), 2);
    }

    public function recompute(): void
    {
        $this->forceFill([
            'progress' => $this->computeProgress(),
        ])->saveQuietly();
    }
}