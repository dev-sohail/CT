<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    protected $table = 'wiki_page_versions';

    protected $fillable = [
        'page_id',
        'title',
        'content',
        'blocks',
        'note',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
