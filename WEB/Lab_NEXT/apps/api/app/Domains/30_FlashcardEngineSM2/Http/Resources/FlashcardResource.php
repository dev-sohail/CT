<?php

namespace App\Domains\FlashcardEngineSM2\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'deck_id' => $this->deck_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'ease_factor' => $this->ease_factor,
            'interval_days' => $this->interval_days,
            'repetitions' => $this->repetitions,
            'due_at' => $this->due_at?->toIso8601String(),
            'last_reviewed_at' => $this->last_reviewed_at?->toIso8601String(),
            'is_due' => $this->due_at === null || $this->due_at->lte(now()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}