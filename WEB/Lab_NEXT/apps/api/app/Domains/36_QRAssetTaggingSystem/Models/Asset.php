<?php

namespace App\Domains\QRAssetTaggingSystem\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'location',
        'qr_token',
        'status',
        'value',
        'purchased_at',
        'warranty_until',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'value' => 'decimal:2',
        'purchased_at' => 'date',
        'warranty_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset): void {
            $asset->qr_token ??= Str::random(24);
        });
    }

    public function getQrPayloadAttribute(): string
    {
        return "ctlab://scan/asset/{$this->qr_token}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }
        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('location', 'like', "%{$term}%"));
    }
}
