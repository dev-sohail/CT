<?php

namespace App\Domains\NotificationHubAndEventBus\Services;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\NotificationHubAndEventBus\Models\NotificationPreference;
use Illuminate\Notifications\Notification;

class NotificationDispatcher
{
    public function dispatch(User $user, Notification $notification, string $type): void
    {
        $preference = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['enabled' => true, 'channels' => NotificationPreference::DEFAULT_CHANNELS]
        );

        if (! $preference->enabled) {
            return;
        }

        $channels = $preference->channels ?: NotificationPreference::DEFAULT_CHANNELS;

        if (in_array('database', $channels, true)) {
            $user->notify($notification);
        }
    }
}
