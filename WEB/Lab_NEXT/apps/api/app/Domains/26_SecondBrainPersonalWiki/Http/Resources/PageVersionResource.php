<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\PageVersion;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin PageVersion */
class PageVersionResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'title' => $this->title,
            'content' => $this->content,
            'blocks' => $this->blocks,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
