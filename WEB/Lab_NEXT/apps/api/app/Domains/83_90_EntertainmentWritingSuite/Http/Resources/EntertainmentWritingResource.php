<?php

namespace App\Domains\EntertainmentWritingSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntertainmentWritingResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'title' => $this->title, 'creator' => $this->creator, 'status' => $this->status, 'rating' => $this->rating, 'record_date' => $this->record_date?->toDateString(), 'description' => $this->description, 'tags' => $this->tags, 'details' => $this->details];
    }
}
