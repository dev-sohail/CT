<?php

namespace App\Domains\SleepAndRecoveryTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SleepLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'sleep_date' => $this->sleep_date?->toDateString(), 'bedtime' => $this->bedtime?->toIso8601String(), 'wake_time' => $this->wake_time?->toIso8601String(), 'duration_minutes' => $this->duration_minutes, 'quality' => $this->quality, 'interruptions' => $this->interruptions, 'deep_minutes' => $this->deep_minutes, 'light_minutes' => $this->light_minutes, 'rem_minutes' => $this->rem_minutes, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
