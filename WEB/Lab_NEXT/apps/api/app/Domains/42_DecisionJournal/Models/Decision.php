<?php

namespace App\Domains\DecisionJournal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    protected $fillable = ['user_id', 'title', 'context', 'options', 'decision', 'rationale', 'confidence', 'status', 'decided_at', 'review_at', 'outcome', 'tags', 'metadata'];

    protected $casts = ['options' => 'array', 'tags' => 'array', 'metadata' => 'array', 'confidence' => 'integer', 'decided_at' => 'date', 'review_at' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
