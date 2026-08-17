<?php

namespace App\Domains\DecisionJournal\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DecisionResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'context' => $this->context, 'options' => $this->options, 'decision' => $this->decision, 'rationale' => $this->rationale, 'confidence' => $this->confidence, 'status' => $this->status, 'decided_at' => $this->decided_at?->toDateString(), 'review_at' => $this->review_at?->toDateString(), 'outcome' => $this->outcome, 'tags' => $this->tags, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
