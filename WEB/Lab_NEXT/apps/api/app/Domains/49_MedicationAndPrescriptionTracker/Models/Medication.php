<?php

namespace App\Domains\MedicationAndPrescriptionTracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    protected $fillable = ['user_id', 'name', 'generic_name', 'dosage', 'form', 'frequency', 'schedule', 'started_on', 'ends_on', 'prescriber', 'status', 'instructions', 'notes', 'metadata'];
    protected $casts = ['schedule' => 'array', 'started_on' => 'date', 'ends_on' => 'date', 'metadata' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id'); }
    public function logs(): HasMany { return $this->hasMany(MedicationLog::class); }
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
