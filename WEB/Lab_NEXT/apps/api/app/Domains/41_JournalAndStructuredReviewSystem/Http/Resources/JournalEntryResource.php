<?php

namespace App\Domains\JournalAndStructuredReviewSystem\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'entry_date' => $this->entry_date?->toDateString(),
            'title' => $this->title,
            'body' => $this->body,
            'mood' => $this->mood,
            'energy' => $this->energy,
            'gratitude' => $this->gratitude,
            'wins' => $this->wins,
            'lessons' => $this->lessons,
            'tags' => $this->tags,
            'is_private' => (bool) $this->is_private,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
