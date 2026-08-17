<?php

namespace App\Domains\DigitalMediaLibrary\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'creator' => $this->creator,
            'year' => $this->year,
            'genre' => $this->genre,
            'tags' => $this->tags,
            'rating' => $this->rating,
            'status' => $this->status,
            'started_at' => $this->started_at?->toDateString(),
            'finished_at' => $this->finished_at?->toDateString(),
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}