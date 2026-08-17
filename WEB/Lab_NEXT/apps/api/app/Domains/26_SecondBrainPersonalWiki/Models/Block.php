<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    protected $table = 'wiki_blocks';

    protected $fillable = [
        'page_id',
        'parent_id',
        'type',
        'content',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'meta' => 'array',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_id')->orderBy('sort_order');
    }
}
