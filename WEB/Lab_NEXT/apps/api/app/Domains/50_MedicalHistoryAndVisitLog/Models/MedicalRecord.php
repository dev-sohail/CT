<?php

namespace App\Domains\MedicalHistoryAndVisitLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    protected $fillable = ['user_id', 'record_type', 'record_date', 'title', 'provider', 'facility', 'diagnosis', 'symptoms', 'treatment', 'medications', 'follow_up_date', 'status', 'notes', 'metadata'];
    protected $casts = ['record_date' => 'date', 'follow_up_date' => 'date', 'medications' => 'array', 'metadata' => 'array'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id'); }
    public function scopeForUser(Builder $query, int $userId): Builder { return $query->where('user_id', $userId); }
}
