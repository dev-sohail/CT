<?php

namespace App\Domains\HydrationAndMovementReminderEngine\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HydrationLog extends Model
{
    protected $fillable = ['user_id', 'drank_at', 'milliliters', 'beverage'];
    protected $casts = ['drank_at' => 'datetime'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
