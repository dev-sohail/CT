<?php

namespace App\Domains\FileAutomationWatcher\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileWatcherLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_watcher_rule_id' => $this->file_watcher_rule_id,
            'filename' => $this->filename,
            'status' => $this->status,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}