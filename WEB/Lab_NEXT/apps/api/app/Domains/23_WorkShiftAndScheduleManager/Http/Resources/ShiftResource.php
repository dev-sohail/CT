<?php

namespace App\Domains\WorkShiftAndScheduleManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'location' => $this->location,
            'client' => $this->client,
            'hourly_rate' => $this->hourly_rate,
            'status' => $this->status,
            'notes' => $this->notes,
            'duration_hours' => $this->durationHours(),
            'estimated_earnings' => $this->estimatedEarnings(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}