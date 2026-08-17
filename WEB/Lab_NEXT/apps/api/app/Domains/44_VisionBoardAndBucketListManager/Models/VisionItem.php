<?php

namespace App\Domains\VisionBoardAndBucketListManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisionItem extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'type', 'category', 'status', 'priority', 'target_date', 'completed_at', 'image_url', 'tags', 'metadata'];

    protected $casts = ['target_date' => 'date', 'completed_at' => 'datetime', 'tags' => 'array', 'metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
