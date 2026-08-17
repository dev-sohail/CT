<?php

namespace App\Domains\VisionBoardAndBucketListManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VisionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'category' => $this->category,
            'status' => $this->status,
            'priority' => $this->priority,
            'target_date' => $this->target_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'image_url' => $this->image_url,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
