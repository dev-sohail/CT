<?php

namespace App\Domains\SubscriptionAndRecurringPaymentTracker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'name',
        'company',
        'category',
        'amount',
        'currency',
        'billing_cycle',
        'started_at',
        'next_billing_at',
        'payment_method',
        'auto_renew',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'started_at' => 'date',
        'next_billing_at' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function monthlyEquivalent(): float
    {
        return match ($this->billing_cycle) {
            'weekly' => (float) $this->amount * 52 / 12,
            'quarterly' => (float) $this->amount / 3,
            'yearly' => (float) $this->amount / 12,
            'one_time' => 0.0,
            default => (float) $this->amount,
        };
    }
}