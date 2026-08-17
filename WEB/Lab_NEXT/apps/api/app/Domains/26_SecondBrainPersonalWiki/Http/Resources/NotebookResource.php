<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Notebook */
class NotebookResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'sections' => $this->whenLoaded('sections', fn () => SectionResource::collection($this->sections)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
