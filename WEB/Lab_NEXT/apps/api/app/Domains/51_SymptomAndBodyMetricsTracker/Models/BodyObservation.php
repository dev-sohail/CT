<?php

namespace App\Domains\SymptomAndBodyMetricsTracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyObservation extends Model
{
    protected $fillable = ['user_id', 'observed_at', 'observation_type', 'name', 'value', 'unit', 'severity', 'description', 'metadata'];
    protected $casts = ['observed_at' => 'datetime', 'value' => 'decimal:2', 'metadata' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id'); }
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
