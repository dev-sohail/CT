<?php

namespace App\Domains\CalendarAndSchedulingKernel\Http\Resources;

use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin CalendarEvent */
class CalendarEventResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'recurrence_rule' => $this->recurrence_rule,
            'recurrence_ends_at' => $this->recurrence_ends_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}