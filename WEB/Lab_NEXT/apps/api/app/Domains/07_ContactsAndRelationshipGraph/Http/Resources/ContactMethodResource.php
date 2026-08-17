<?php

namespace App\Domains\ContactsAndRelationshipGraph\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactMethodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'label' => $this->label,
            'is_primary' => (bool) $this->is_primary,
        ];
    }
}