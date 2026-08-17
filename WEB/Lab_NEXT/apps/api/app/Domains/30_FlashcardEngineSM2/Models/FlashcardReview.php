<?php

namespace App\Domains\FlashcardEngineSM2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReview extends Model
{
    protected $table = 'flashcard_reviews';

    protected $fillable = [
        'flashcard_id',
        'quality',
        'created_at',
    ];

    protected $casts = [
        'quality' => 'integer',
    ];

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class, 'flashcard_id');
    }
}