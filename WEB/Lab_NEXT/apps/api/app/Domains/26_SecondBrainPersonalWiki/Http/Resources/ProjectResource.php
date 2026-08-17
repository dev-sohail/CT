<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Project;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Project */
class ProjectResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'pages' => $this->whenLoaded('pages', fn () => PageResource::collection($this->pages)),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
