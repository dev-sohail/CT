<?php

namespace App\Domains\HomeImprovementProjectPlanner\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeProject extends Model
{
    protected $fillable = ['user_id', 'name', 'description', 'room', 'status', 'priority', 'started_on', 'target_date', 'completed_on', 'budget', 'spent', 'tasks', 'metadata'];
    protected $casts = ['started_on' => 'date', 'target_date' => 'date', 'completed_on' => 'date', 'budget' => 'decimal:2', 'spent' => 'decimal:2', 'tasks' => 'array', 'metadata' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
