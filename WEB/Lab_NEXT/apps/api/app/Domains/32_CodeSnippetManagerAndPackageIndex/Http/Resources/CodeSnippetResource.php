<?php

namespace App\Domains\CodeSnippetManagerAndPackageIndex\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CodeSnippetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'language' => $this->language,
            'code' => $this->code,
            'tags' => $this->tags,
            'package_type' => $this->package_type,
            'package_name' => $this->package_name,
            'package_version' => $this->package_version,
            'favorite' => (bool) $this->favorite,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}