<?php

namespace App\Domains\BookmarkAndReadLaterArchive\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'saved_content_excerpt' => $this->saved_content ? substr($this->saved_content, 0, 300) : null,
            'status' => $this->status,
            'favicon_url' => $this->favicon_url,
            'domain' => $this->domain,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}