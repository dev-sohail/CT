<?php

namespace App\Domains\RuleEngineAndWorkflowBuilder\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleActionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'config' => $this->config,
            'order' => $this->order,
        ];
    }
}