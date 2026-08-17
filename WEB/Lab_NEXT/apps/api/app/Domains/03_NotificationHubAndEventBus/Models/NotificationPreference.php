<?php

namespace App\Domains\NotificationHubAndEventBus\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    public const DEFAULT_CHANNELS = ['database'];

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'type',
        'enabled',
        'channels',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'channels' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
