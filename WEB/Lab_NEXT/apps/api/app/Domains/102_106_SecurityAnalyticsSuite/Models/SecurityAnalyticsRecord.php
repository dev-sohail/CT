<?php

namespace App\Domains\SecurityAnalyticsSuite\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SecurityAnalyticsRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'name', 'status', 'value', 'recorded_at', 'description', 'details'];
    protected $casts = ['value' => 'decimal:2', 'recorded_at' => 'datetime', 'details' => 'array'];
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
