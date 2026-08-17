<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Workspace */
class WorkspaceResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'is_default' => $this->is_default,
            'notebooks' => $this->whenLoaded('notebooks', fn () => NotebookResource::collection($this->notebooks)),
            'projects' => $this->whenLoaded('projects', fn () => ProjectResource::collection($this->projects)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
