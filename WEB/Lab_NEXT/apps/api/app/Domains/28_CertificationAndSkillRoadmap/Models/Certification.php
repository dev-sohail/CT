<?php

namespace App\Domains\CertificationAndSkillRoadmap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Certification extends Model
{
    protected $table = 'certifications';

    protected $fillable = [
        'user_id',
        'title',
        'issuer',
        'status',
        'issued_at',
        'expiry_at',
        'credential_url',
        'skills',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expiry_at' => 'date',
        'skills' => 'array',
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

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_at) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->expiry_at, false);
    }
}