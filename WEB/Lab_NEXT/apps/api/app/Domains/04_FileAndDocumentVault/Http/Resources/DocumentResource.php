<?php

namespace App\Domains\FileAndDocumentVault\Http\Resources;

use App\Domains\FileAndDocumentVault\Models\Document;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Document */
class DocumentResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'folder' => $this->folder,
            'current_version' => $this->current_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
