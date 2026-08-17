<?php

namespace App\Domains\ContactsAndRelationshipGraph\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RelationshipResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'related_person_id' => $this->related_person_id,
            'type' => $this->type,
            'note' => $this->note,
            'related_person' => $this->whenLoaded('relatedPerson') ? PersonResource::make($this->relatedPerson) : null,
        ];
    }
}