<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Task;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Task */
class TaskResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'done' => $this->done,
            'due_date' => $this->due_date,
            'priority' => $this->priority,
            'taskable_type' => $this->taskable_type,
            'taskable_id' => $this->taskable_id,
            'notes' => $this->notes,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
