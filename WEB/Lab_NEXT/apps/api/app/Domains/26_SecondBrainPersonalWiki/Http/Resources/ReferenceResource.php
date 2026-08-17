<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Reference;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Reference */
class ReferenceResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type,
            'author' => $this->author,
            'credibility' => $this->credibility,
            'notes' => $this->notes,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
