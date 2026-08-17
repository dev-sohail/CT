<?php

namespace App\Domains\PersonalScorecardLifeKPITracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScorecardMetric extends Model
{
    protected $fillable = ['user_id', 'name', 'category', 'unit', 'target', 'direction', 'frequency', 'active', 'metadata'];
    protected $casts = ['target' => 'decimal:2', 'active' => 'boolean', 'metadata' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id'); }
    public function measurements(): HasMany { return $this->hasMany(ScorecardMeasurement::class, 'metric_id'); }
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
