<?php

namespace App\Domains\FileAutomationWatcher\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileWatcherRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source_disk' => $this->source_disk,
            'source_path' => $this->source_path,
            'pattern' => $this->pattern,
            'action' => $this->action,
            'destination_path' => $this->destination_path,
            'tag_keyword' => $this->tag_keyword,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}