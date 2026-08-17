<?php

namespace App\Domains\FlashcardEngineSM2\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeckResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'flashcard_count' => $this->whenLoaded('flashcards', fn () => $this->flashcards->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}