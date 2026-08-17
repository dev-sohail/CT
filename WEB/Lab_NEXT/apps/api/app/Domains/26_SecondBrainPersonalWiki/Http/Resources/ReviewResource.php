<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Review;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Review */
class ReviewResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'page_id' => $this->page_id,
            'type' => $this->type,
            'status' => $this->status,
            'scheduled_for' => $this->scheduled_for,
            'next_review_at' => $this->next_review_at,
            'last_reviewed_at' => $this->last_reviewed_at,
            'notes' => $this->notes,
            'page' => $this->whenLoaded('page', fn () => PageResource::make($this->page)),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
