<?php

namespace App\Domains\ScheduledTaskAndCronManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledTaskLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'scheduled_task_id' => $this->scheduled_task_id,
            'status' => $this->status,
            'output' => $this->output,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}