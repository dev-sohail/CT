<?php

namespace App\Domains\PersonalScorecardLifeKPITracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScorecardMeasurement extends Model
{
    protected $fillable = ['metric_id', 'user_id', 'measured_on', 'value', 'note'];
    protected $casts = ['measured_on' => 'date', 'value' => 'decimal:2'];
    public function metric(): BelongsTo { return $this->belongsTo(ScorecardMetric::class, 'metric_id'); }
}
