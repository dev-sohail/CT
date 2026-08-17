<?php

namespace App\Domains\ScheduledTaskAndCronManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'command' => $this->command,
            'cron_expression' => $this->cron_expression,
            'is_active' => (bool) $this->is_active,
            'arguments' => $this->arguments,
            'last_status' => $this->last_status,
            'last_output' => $this->last_output,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}