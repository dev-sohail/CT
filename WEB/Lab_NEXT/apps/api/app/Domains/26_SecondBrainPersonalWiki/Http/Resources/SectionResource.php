<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Section;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Section */
class SectionResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'notebook_id' => $this->notebook_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'pages' => $this->whenLoaded('pages', fn () => PageResource::collection($this->pages)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
