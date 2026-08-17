<?php

namespace App\Domains\ImportExportAndDataPortability\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'format' => $this->format,
            'domains' => $this->domains,
            'item_counts' => $this->item_counts,
            'error' => $this->error,
            'download_url' => $this->status === 'completed' && $this->file_path
                ? url("/api/v1/exports/{$this->id}/download")
                : null,
            'filename' => $this->status === 'completed' ? $this->downloadName() : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}