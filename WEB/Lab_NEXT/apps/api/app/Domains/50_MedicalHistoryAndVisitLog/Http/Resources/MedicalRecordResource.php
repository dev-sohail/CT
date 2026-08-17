<?php

namespace App\Domains\MedicalHistoryAndVisitLog\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'record_date' => $this->record_date?->toDateString(), 'title' => $this->title, 'provider' => $this->provider, 'facility' => $this->facility, 'diagnosis' => $this->diagnosis, 'symptoms' => $this->symptoms, 'treatment' => $this->treatment, 'medications' => $this->medications, 'follow_up_date' => $this->follow_up_date?->toDateString(), 'status' => $this->status, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
