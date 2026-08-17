<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Page;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Page */
class PageResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'content' => $this->content,
            'icon' => $this->icon,
            'type' => $this->type,
            'status' => $this->status,
            'difficulty' => $this->difficulty,
            'is_favorite' => $this->is_favorite,
            'pinned_at' => $this->pinned_at,
            'sort_order' => $this->sort_order,
            'blocks' => $this->whenLoaded('blocks', fn () => BlockResource::collection($this->blocks)),
            'tags' => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)),
            'versions' => $this->whenLoaded('versions', fn () => PageVersionResource::collection($this->versions)),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
