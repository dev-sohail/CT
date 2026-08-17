<?php

namespace App\Domains\ResearchAndPDFAnnotationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaperAnnotationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'paper_id' => $this->paper_id,
            'page' => $this->page,
            'text' => $this->text,
            'note' => $this->note,
            'color' => $this->color,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}