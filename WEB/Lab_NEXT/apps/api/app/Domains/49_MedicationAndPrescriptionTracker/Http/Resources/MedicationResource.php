<?php

namespace App\Domains\MedicationAndPrescriptionTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'generic_name' => $this->generic_name, 'dosage' => $this->dosage, 'form' => $this->form, 'frequency' => $this->frequency, 'schedule' => $this->schedule, 'started_on' => $this->started_on?->toDateString(), 'ends_on' => $this->ends_on?->toDateString(), 'prescriber' => $this->prescriber, 'status' => $this->status, 'instructions' => $this->instructions, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
