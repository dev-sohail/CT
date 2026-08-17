<?php

namespace App\Domains\HomeImprovementProjectPlanner\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description, 'room' => $this->room, 'status' => $this->status, 'priority' => $this->priority, 'started_on' => $this->started_on?->toDateString(), 'target_date' => $this->target_date?->toDateString(), 'completed_on' => $this->completed_on?->toDateString(), 'budget' => $this->budget !== null ? (float) $this->budget : null, 'spent' => (float) $this->spent, 'tasks' => $this->tasks, 'metadata' => $this->metadata];
    }
}
