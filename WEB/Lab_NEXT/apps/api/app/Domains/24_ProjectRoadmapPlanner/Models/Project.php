<?php

namespace App\Domains\ProjectRoadmapPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'start_at',
        'target_end_at',
        'priority',
        'client',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'target_end_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')->orderBy('sort_order');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function completedMilestones(): int
    {
        return $this->milestones()->where('status', 'completed')->count();
    }

    public function milestoneProgress(): int
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }
        return (int) round(($this->completedMilestones() / $total) * 100);
    }
}