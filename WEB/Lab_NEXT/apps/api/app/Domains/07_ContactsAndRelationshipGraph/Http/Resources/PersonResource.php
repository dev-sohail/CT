<?php

namespace App\Domains\ContactsAndRelationshipGraph\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'birthday' => $this->birthday?->toDateString(),
            'avatar_url' => $this->avatar_url,
            'notes' => $this->notes,
            'relationship_type' => $this->relationship_type,
            'metadata' => $this->metadata,
            'contact_methods' => ContactMethodResource::collection($this->whenLoaded('contactMethods')),
            'relationships' => RelationshipResource::collection($this->whenLoaded('relationships')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}