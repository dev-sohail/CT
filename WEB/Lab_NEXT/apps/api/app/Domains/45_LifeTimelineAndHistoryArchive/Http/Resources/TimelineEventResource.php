<?php

namespace App\Domains\LifeTimelineAndHistoryArchive\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEventResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'event_date' => $this->event_date?->toDateString(), 'title' => $this->title, 'description' => $this->description, 'type' => $this->type, 'category' => $this->category, 'significance' => $this->significance, 'location' => $this->location, 'people' => $this->people, 'tags' => $this->tags, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
