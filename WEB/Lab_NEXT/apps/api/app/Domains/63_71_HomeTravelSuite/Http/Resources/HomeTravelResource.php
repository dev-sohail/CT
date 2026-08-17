<?php

namespace App\Domains\HomeTravelSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeTravelResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'name' => $this->name, 'description' => $this->description, 'location' => $this->location, 'status' => $this->status, 'date' => $this->date?->toDateString(), 'due_date' => $this->due_date?->toDateString(), 'amount' => $this->amount !== null ? (float) $this->amount : null, 'unit' => $this->unit, 'details' => $this->details, 'metadata' => $this->metadata];
    }
}
