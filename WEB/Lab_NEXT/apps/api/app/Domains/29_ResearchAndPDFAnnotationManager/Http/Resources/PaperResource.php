<?php

namespace App\Domains\ResearchAndPDFAnnotationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaperResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'authors' => $this->authors,
            'year' => $this->year,
            'source' => $this->source,
            'url' => $this->url,
            'file_path' => $this->file_path,
            'abstract' => $this->abstract,
            'status' => $this->status,
            'rating' => $this->rating,
            'annotation_count' => $this->whenLoaded('annotations', fn () => $this->annotations->count()),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}