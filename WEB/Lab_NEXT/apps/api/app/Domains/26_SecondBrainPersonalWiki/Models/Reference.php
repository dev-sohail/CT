<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reference extends Model
{
    use SoftDeletes;

    protected $table = 'wiki_references';

    protected $fillable = [
        'workspace_id',
        'title',
        'url',
        'type',
        'author',
        'credibility',
        'notes',
    ];

    protected $casts = [
        'credibility' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
