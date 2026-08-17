<?php

namespace App\Domains\GoalHierarchyAndOKRTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'description' => $this->description,
            'level' => $this->level,
            'start_at' => $this->start_at?->toIso8601String(),
            'target_at' => $this->target_at?->toIso8601String(),
            'status' => $this->status,
            'progress' => round((float) $this->progress, 2),
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
            'key_results' => KeyResultResource::collection($this->whenLoaded('keyResults')),
            'children' => self::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}