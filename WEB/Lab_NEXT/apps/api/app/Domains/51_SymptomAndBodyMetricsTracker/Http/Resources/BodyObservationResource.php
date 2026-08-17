<?php

namespace App\Domains\SymptomAndBodyMetricsTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BodyObservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'observed_at' => $this->observed_at?->toIso8601String(), 'observation_type' => $this->observation_type, 'name' => $this->name, 'value' => $this->value !== null ? (float) $this->value : null, 'unit' => $this->unit, 'severity' => $this->severity, 'description' => $this->description, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
