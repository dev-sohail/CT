<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConditionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'order' => $this->order,
        ];
    }
}