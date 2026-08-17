<?php

namespace App\Domains\DashboardAndWidgetFramework\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WidgetLayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'widget_key' => $this->widget_key,
            'position' => [
                'x' => $this->position_x,
                'y' => $this->position_y,
            ],
            'size' => [
                'width' => $this->width,
                'height' => $this->height,
            ],
            'enabled' => (bool) $this->enabled,
            'refresh_interval' => $this->refresh_interval,
        ];
    }
}