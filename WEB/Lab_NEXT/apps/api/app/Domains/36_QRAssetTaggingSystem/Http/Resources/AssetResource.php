<?php

namespace App\Domains\QRAssetTaggingSystem\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location,
            'qr_token' => $this->qr_token,
            'qr_payload' => $this->qr_payload,
            'status' => $this->status,
            'value' => $this->value !== null ? (float) $this->value : null,
            'purchased_at' => $this->purchased_at?->toDateString(),
            'warranty_until' => $this->warranty_until?->toDateString(),
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with($request): array
    {
        return [
            'qr_payload' => $this->qr_payload,
        ];
    }
}
