<?php

namespace App\Domains\DashboardAndWidgetFramework\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WidgetRegistrationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'default_size' => [
                'x' => $this->default_size_x,
                'y' => $this->default_size_y,
            ],
            'refresh_interval' => $this->refresh_interval,
            'enabled' => (bool) $this->enabled,
        ];
    }
}