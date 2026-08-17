<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Resources;

use App\Domains\SecondBrainPersonalWiki\Models\Question;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin Question */
class QuestionResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'answer' => $this->answer,
            'status' => $this->status,
            'difficulty' => $this->difficulty,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
