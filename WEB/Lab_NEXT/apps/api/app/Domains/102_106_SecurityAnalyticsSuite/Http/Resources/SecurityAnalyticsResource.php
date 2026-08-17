<?php

namespace App\Domains\SecurityAnalyticsSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SecurityAnalyticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'name' => $this->name, 'status' => $this->status, 'value' => $this->value !== null ? (float) $this->value : null, 'recorded_at' => $this->recorded_at?->toIso8601String(), 'description' => $this->description, 'details' => $this->details];
    }
}
