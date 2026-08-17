<?php

namespace App\Domains\TaggingAndCategorizationEngine\Http\Resources;

use App\Domains\TaggingAndCategorizationEngine\Models\Tag;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Tag */
class TagResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'count' => $this->taggables_count ?? null,
        ];
    }
}
