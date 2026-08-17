<?php

namespace App\Domains\FlashcardEngineSM2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Flashcard extends Model
{
    protected $table = 'flashcards';

    protected $fillable = [
        'deck_id',
        'user_id',
        'question',
        'answer',
        'ease_factor',
        'interval_days',
        'repetitions',
        'due_at',
        'last_reviewed_at',
    ];

    protected $casts = [
        'ease_factor' => 'float',
        'interval_days' => 'integer',
        'repetitions' => 'integer',
        'due_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class, 'deck_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlashcardReview::class, 'flashcard_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('due_at')->orWhere('due_at', '<=', now());
        });
    }
}