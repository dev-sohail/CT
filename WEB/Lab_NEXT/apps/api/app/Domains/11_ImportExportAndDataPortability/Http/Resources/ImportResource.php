<?php

namespace App\Domains\ImportExportAndDataPortability\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'format' => $this->format,
            'status' => $this->status,
            'item_counts' => $this->item_counts,
            'errors' => $this->errors,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}