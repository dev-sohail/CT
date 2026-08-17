<?php

namespace App\Domains\FinanceSuite\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinanceRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'record_type' => $this->record_type, 'name' => $this->name, 'amount' => $this->amount !== null ? (float) $this->amount : null, 'target_amount' => $this->target_amount !== null ? (float) $this->target_amount : null, 'current_amount' => $this->current_amount !== null ? (float) $this->current_amount : null, 'currency' => $this->currency, 'status' => $this->status, 'due_date' => $this->due_date?->toDateString(), 'institution' => $this->institution, 'category' => $this->category, 'notes' => $this->notes, 'metadata' => $this->metadata, 'created_at' => $this->created_at?->toIso8601String(), 'updated_at' => $this->updated_at?->toIso8601String()];
    }
}
