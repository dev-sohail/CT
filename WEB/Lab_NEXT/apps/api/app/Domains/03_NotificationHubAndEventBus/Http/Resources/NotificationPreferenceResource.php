<?php

namespace App\Domains\NotificationHubAndEventBus\Http\Resources;

use App\Domains\NotificationHubAndEventBus\Models\NotificationPreference;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin NotificationPreference */
class NotificationPreferenceResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'enabled' => $this->enabled,
            'channels' => $this->channels,
        ];
    }
}
