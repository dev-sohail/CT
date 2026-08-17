<?php

namespace App\Domains\ReceiptAndWarrantyArchive\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $table = 'receipts';

    protected $fillable = [
        'user_id',
        'title',
        'merchant',
        'category',
        'amount',
        'currency',
        'purchased_at',
        'warranty_until',
        'receipt_number',
        'items',
        'document_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchased_at' => 'date',
        'warranty_until' => 'date',
        'items' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\FileAndDocumentVault\Models\Document::class, 'document_id');
    }

    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->warranty_until) {
            return 'none';
        }
        if ($this->warranty_until->lt(now()->startOfDay())) {
            return 'expired';
        }
        if ($this->warranty_until->lte(now()->addDays(30)->endOfDay())) {
            return 'expiring_soon';
        }
        return 'active';
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
            ->where('title', 'like', "%{$term}%")
            ->orWhere('merchant', 'like', "%{$term}%")
            ->orWhere('receipt_number', 'like', "%{$term}%"));
    }
}