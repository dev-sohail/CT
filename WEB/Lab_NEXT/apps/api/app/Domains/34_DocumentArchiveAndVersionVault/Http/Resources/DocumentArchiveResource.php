<?php

namespace App\Domains\DocumentArchiveAndVersionVault\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentArchiveResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'tags' => $this->tags,
            'document_id' => $this->document_id,
            'source' => $this->source,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'status' => $this->status,
            'is_expired' => $this->expires_at !== null && $this->expires_at->lt(now()),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}