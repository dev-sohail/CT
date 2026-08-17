<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Template;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Template */
class TemplateResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'blocks' => $this->blocks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
