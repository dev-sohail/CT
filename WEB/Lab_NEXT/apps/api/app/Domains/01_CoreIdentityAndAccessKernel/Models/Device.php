<?php

namespace App\Domains\CoreIdentityAndAccessKernel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $table = 'identity_devices';

    protected $fillable = [
        'user_id',
        'name',
        'device_id',
        'platform',
        'browser',
        'ip_address',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
