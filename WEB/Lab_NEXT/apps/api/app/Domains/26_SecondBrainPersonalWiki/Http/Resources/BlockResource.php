<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Block;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Block */
class BlockResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type,
            'content' => $this->content,
            'meta' => $this->meta,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
