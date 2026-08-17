<?php

namespace App\Domains\HomeMaintenanceScheduler\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTask extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'category', 'location', 'due_on', 'completed_on', 'recurrence_days', 'cost', 'status', 'priority', 'notes', 'metadata'];
    protected $casts = ['due_on' => 'date', 'completed_on' => 'date', 'cost' => 'decimal:2', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
