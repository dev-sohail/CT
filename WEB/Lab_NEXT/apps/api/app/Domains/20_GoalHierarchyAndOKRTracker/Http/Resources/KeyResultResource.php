<?php

namespace App\Domains\GoalHierarchyAndOKRTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KeyResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'goal_id' => $this->goal_id,
            'title' => $this->title,
            'current_value' => $this->current_value,
            'target_value' => $this->target_value,
            'unit' => $this->unit,
            'status' => $this->status,
            'progress' => round((float) $this->progress, 2),
            'sort_order' => $this->sort_order,
        ];
    }
}