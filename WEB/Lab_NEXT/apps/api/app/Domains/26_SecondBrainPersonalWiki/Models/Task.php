<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use App\Domains\UnifiedSearchEngine\Contracts\Searchable;
use App\Domains\UnifiedSearchEngine\Services\InteractsWithSearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model implements Searchable
{
    use InteractsWithSearchIndex;
    use SoftDeletes;

    protected $table = 'wiki_tasks';

    protected $fillable = [
        'workspace_id',
        'title',
        'done',
        'due_date',
        'priority',
        'taskable_type',
        'taskable_id',
        'notes',
    ];

    protected $casts = [
        'done' => 'boolean',
        'due_date' => 'date',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function searchOwnerId(): int
    {
        return $this->workspace->user_id;
    }

    public function searchIndexTitle(): string
    {
        return $this->title;
    }

    public function searchIndexContent(): string
    {
        return (string) $this->notes;
    }

    public function searchIndexWeight(): int
    {
        return 5;
    }
}
