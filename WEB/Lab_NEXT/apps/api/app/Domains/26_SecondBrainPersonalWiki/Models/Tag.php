<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'wiki_tags';

    protected $fillable = [
        'name',
        'color',
    ];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'wiki_page_tag', 'tag_id', 'page_id');
    }
}
