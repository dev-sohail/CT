<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $table = 'wiki_questions';

    protected $fillable = [
        'workspace_id',
        'title',
        'answer',
        'status',
        'difficulty',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
