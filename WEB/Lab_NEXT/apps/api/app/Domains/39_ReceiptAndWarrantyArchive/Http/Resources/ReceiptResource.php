<?php

namespace App\Domains\ReceiptAndWarrantyArchive\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'merchant' => $this->merchant,
            'category' => $this->category,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'purchased_at' => $this->purchased_at?->toDateString(),
            'warranty_until' => $this->warranty_until?->toDateString(),
            'warranty_status' => $this->warranty_status,
            'receipt_number' => $this->receipt_number,
            'items' => $this->items,
            'document_id' => $this->document_id,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}