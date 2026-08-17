<?php

namespace App\Domains\ProjectRoadmapPlanner\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'start_at' => $this->start_at?->toIso8601String(),
            'target_end_at' => $this->target_end_at?->toIso8601String(),
            'priority' => $this->priority,
            'client' => $this->client,
            'milestone_progress' => $this->milestoneProgress(),
            'metadata' => $this->metadata,
            'milestones' => ProjectMilestoneResource::collection($this->whenLoaded('milestones')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}