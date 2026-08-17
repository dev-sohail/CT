<?php

namespace App\Domains\CareerBusinessSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CareerBusinessResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'title' => $this->title, 'organization' => $this->organization, 'status' => $this->status, 'record_date' => $this->record_date?->toDateString(), 'next_date' => $this->next_date?->toDateString(), 'amount' => $this->amount !== null ? (float) $this->amount : null, 'description' => $this->description, 'details' => $this->details, 'metadata' => $this->metadata];
    }
}
