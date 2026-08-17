<?php

namespace App\Domains\UnifiedSearchEngine\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchEntry extends Model
{
    protected $table = 'search_index';

    protected $fillable = [
        'user_id',
        'searchable_type',
        'searchable_id',
        'title',
        'content',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }
}
