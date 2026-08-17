<?php

namespace App\Domains\CertificationAndSkillRoadmap\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CertificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'issuer' => $this->issuer,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toDateString(),
            'expiry_at' => $this->expiry_at?->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry(),
            'credential_url' => $this->credential_url,
            'skills' => $this->skills,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}