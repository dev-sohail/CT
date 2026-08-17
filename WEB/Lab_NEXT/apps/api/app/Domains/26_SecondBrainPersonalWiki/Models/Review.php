<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $table = 'wiki_reviews';

    protected $fillable = [
        'workspace_id',
        'page_id',
        'type',
        'status',
        'scheduled_for',
        'next_review_at',
        'last_reviewed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'next_review_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
