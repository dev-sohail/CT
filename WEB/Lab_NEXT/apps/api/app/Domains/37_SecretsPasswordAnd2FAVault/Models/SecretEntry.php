<?php

namespace App\Domains\SecretsPasswordAnd2FAVault\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SecretEntry extends Model
{
    protected $table = 'secret_entries';

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'url',
        'username',
        'password_encrypted',
        'notes_encrypted',
        'tags',
        'totp_secret_encrypted',
        'totp_enabled',
        'strength_score',
        'favorite',
        'expires_at',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'totp_enabled' => 'boolean',
        'strength_score' => 'integer',
        'favorite' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password_encrypted'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    public function getPasswordAttribute(): ?string
    {
        if (!$this->password_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->password_encrypted);
    }

    public function setNotesAttribute(?string $value): void
    {
        $this->attributes['notes_encrypted'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    public function getNotesAttribute(): ?string
    {
        if (!$this->notes_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->notes_encrypted);
    }

    public function setTotpSecretAttribute(?string $value): void
    {
        $this->attributes['totp_secret_encrypted'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    public function getTotpSecretAttribute(): ?string
    {
        if (!$this->totp_secret_encrypted) {
            return null;
        }
        return Crypt::decryptString($this->totp_secret_encrypted);
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
            ->orWhere('username', 'like', "%{$term}%")
            ->orWhere('url', 'like', "%{$term}%"));
    }
}