<?php

namespace App\Domains\UnifiedSearchEngine\Services;

use App\Domains\UnifiedSearchEngine\Contracts\Searchable;
use App\Domains\UnifiedSearchEngine\Models\SearchEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SearchService
{
    public function sync(Searchable&Model $model): void
    {
        $entry = SearchEntry::firstOrNew([
            'user_id' => $model->searchOwnerId(),
            'searchable_type' => $model->getMorphClass(),
            'searchable_id' => $model->getKey(),
        ]);

        $entry->title = $model->searchIndexTitle();
        $entry->content = $model->searchIndexContent();
        $entry->weight = $model->searchIndexWeight();
        $entry->save();
    }

    public function remove(Searchable&Model $model): void
    {
        SearchEntry::where('user_id', $model->searchOwnerId())
            ->where('searchable_type', $model->getMorphClass())
            ->where('searchable_id', $model->getKey())
            ->delete();
    }

    /**
     * Deterministic ranked search across every indexed model.
     *
     * @return Collection<int, SearchEntry>
     */
    public function search(int $userId, string $query, ?string $type = null, int $limit = 50): Collection
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return collect();
        }

        $builder = SearchEntry::query()
            ->where('user_id', $userId)
            ->where(function (Builder $sub) use ($needle) {
                $sub->where('title', 'like', "%{$needle}%")
                    ->orWhere('content', 'like', "%{$needle}%");
            });

        if ($type !== null) {
            $builder->where('searchable_type', $type);
        }

        return $builder
            ->orderByRaw('CASE WHEN LOWER(title) LIKE ? THEN 0 ELSE 1 END', ["%{$needle}%"])
            ->orderByDesc('weight')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Rebuild the index for a searchable model.
     */
    public function rebuild(Searchable&Model $model): void
    {
        $this->remove($model);
        $this->sync($model);
    }
}
