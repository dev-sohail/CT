<?php

namespace App\Domains\HomeMaintenanceScheduler\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'description' => $this->description, 'category' => $this->category, 'location' => $this->location, 'due_on' => $this->due_on?->toDateString(), 'completed_on' => $this->completed_on?->toDateString(), 'recurrence_days' => $this->recurrence_days, 'cost' => $this->cost !== null ? (float) $this->cost : null, 'status' => $this->status, 'priority' => $this->priority, 'notes' => $this->notes, 'metadata' => $this->metadata];
    }
}
