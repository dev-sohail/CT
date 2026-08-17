<?php

namespace App\Domains\ResearchAndPDFAnnotationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Paper extends Model
{
    protected $table = 'papers';

    protected $fillable = [
        'user_id',
        'title',
        'authors',
        'year',
        'source',
        'url',
        'file_path',
        'abstract',
        'status',
        'rating',
        'metadata',
    ];

    protected $casts = [
        'authors' => 'array',
        'rating' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(PaperAnnotation::class, 'paper_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}