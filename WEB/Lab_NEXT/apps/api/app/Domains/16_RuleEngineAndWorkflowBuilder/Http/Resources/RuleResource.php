<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'trigger_type' => $this->trigger_type,
            'trigger_config' => $this->trigger_config,
            'conditions_logic' => $this->conditions_logic,
            'conditions' => ConditionResource::collection($this->whenLoaded('conditions')),
            'actions' => RuleActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}