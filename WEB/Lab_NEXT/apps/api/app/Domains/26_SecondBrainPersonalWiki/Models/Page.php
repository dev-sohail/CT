<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use App\Domains\UnifiedSearchEngine\Contracts\Searchable;
use App\Domains\UnifiedSearchEngine\Services\InteractsWithSearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model implements Searchable
{
    use InteractsWithSearchIndex;
    use SoftDeletes;

    protected $table = 'wiki_pages';

    protected $fillable = [
        'section_id',
        'project_id',
        'title',
        'content',
        'icon',
        'type',
        'status',
        'difficulty',
        'is_favorite',
        'pinned_at',
        'sort_order',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'pinned_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'wiki_page_tag', 'page_id', 'tag_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->latest('id');
    }

    public function searchOwnerId(): int
    {
        return $this->section->notebook->workspace->user_id;
    }

    public function searchIndexTitle(): string
    {
        return $this->title;
    }

    public function searchIndexContent(): string
    {
        return trim($this->content."\n".$this->blocks->pluck('content')->implode("\n"));
    }

    public function searchIndexWeight(): int
    {
        return 10;
    }
}
