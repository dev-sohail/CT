<?php

namespace App\Domains\WorkoutAndTrainingPlanner\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type, 'scheduled_on' => $this->scheduled_on?->toDateString(), 'started_at' => $this->started_at?->toIso8601String(), 'completed_at' => $this->completed_at?->toIso8601String(), 'duration_minutes' => $this->duration_minutes, 'calories_burned' => $this->calories_burned, 'status' => $this->status, 'exercises' => $this->exercises, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
