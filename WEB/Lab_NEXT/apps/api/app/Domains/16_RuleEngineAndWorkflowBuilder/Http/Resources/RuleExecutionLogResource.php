<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleExecutionLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rule_id' => $this->rule_id,
            'triggered' => (bool) $this->triggered,
            'context' => $this->context,
            'results' => $this->results,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}