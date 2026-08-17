<?php

namespace App\Domains\HydrationAndMovementReminderEngine\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MovementLog extends Model
{
    protected $fillable = ['user_id', 'moved_at', 'minutes', 'activity', 'steps', 'notes'];
    protected $casts = ['moved_at' => 'datetime'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
