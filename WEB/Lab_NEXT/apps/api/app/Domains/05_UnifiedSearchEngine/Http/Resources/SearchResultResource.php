<?php

namespace App\Domains\UnifiedSearchEngine\Http\Resources;

use App\Domains\UnifiedSearchEngine\Models\SearchEntry;
use Ctlab\Support\Http\Resources\ApiResource;

/** @mixin SearchEntry */
class SearchResultResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'model_type' => $this->searchable_type,
            'model_id' => $this->searchable_id,
            'title' => $this->title,
            'snippet' => $this->snippet($request->string('q')->toString()),
            'weight' => $this->weight,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function snippet(string $query): ?string
    {
        if (! $query || ! $this->content) {
            return null;
        }

        $haystack = mb_strtolower($this->content);
        $needle = mb_strtolower($query);
        $position = mb_strpos($haystack, $needle);

        if ($position === false) {
            return mb_substr($this->content, 0, 160);
        }

        $start = max(0, $position - 40);

        return '…'.mb_substr($this->content, $start, 160).'…';
    }
}
