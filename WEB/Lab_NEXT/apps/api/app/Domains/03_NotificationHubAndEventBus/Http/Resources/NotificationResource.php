<?php

namespace App\Domains\NotificationHubAndEventBus\Http\Resources;

use App\Domains\NotificationHubAndEventBus\Models\UserNotification;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin UserNotification */
class NotificationResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
