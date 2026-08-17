<?php

namespace App\Domains\ProjectRoadmapPlanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $table = 'project_milestones';

    protected $fillable = [
        'project_id',
        'title',
        'due_at',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}