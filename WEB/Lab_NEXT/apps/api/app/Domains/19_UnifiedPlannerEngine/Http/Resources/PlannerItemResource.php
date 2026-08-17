<?php

namespace App\Domains\UnifiedPlannerEngine\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlannerItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'scope' => $this->scope,
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at?->toIso8601String(),
            'status' => $this->status,
            'priority' => $this->priority,
            'recurrence_rule' => $this->recurrence_rule,
            'sort_order' => $this->sort_order,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}