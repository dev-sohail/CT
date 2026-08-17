<?php

namespace App\Domains\TaggingAndCategorizationEngine\Services;

use App\Domains\TaggingAndCategorizationEngine\Models\Tag;
use App\Domains\TaggingAndCategorizationEngine\Models\Taggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TagService
{
    public function ensureTag(int $userId, string $name, ?string $color = null): Tag
    {
        $slug = $this->slugify($name);

        $tag = Tag::firstOrNew(['user_id' => $userId, 'slug' => $slug]);
        $tag->name = $name;
        $tag->color = $color ?? $tag->color;

        return $tag;
    }

    public function attach(int $userId, Model $model, string|array $names, ?string $color = null): void
    {
        foreach ((array) $names as $name) {
            $tag = $this->ensureTag($userId, $name, $color);
            $tag->save();

            Taggable::firstOrCreate([
                'tag_id' => $tag->id,
                'taggable_type' => $model->getMorphClass(),
                'taggable_id' => $model->getKey(),
            ]);
        }
    }

    public function detach(int $userId, Model $model, string|array $names): void
    {
        $slugs = collect((array) $names)->map(fn ($name) => $this->slugify($name));

        $tagIds = Tag::where('user_id', $userId)
            ->whereIn('slug', $slugs)
            ->pluck('id');

        Taggable::where('taggable_type', $model->getMorphClass())
            ->where('taggable_id', $model->getKey())
            ->whereIn('tag_id', $tagIds)
            ->delete();
    }

    public function sync(int $userId, Model $model, array $names): void
    {
        $this->replaceAll($userId, $model, $names);
    }

    public function replaceAll(int $userId, Model $model, array $names): void
    {
        Taggable::where('taggable_type', $model->getMorphClass())
            ->where('taggable_id', $model->getKey())
            ->delete();

        $this->attach($userId, $model, $names);
    }

    /**
     * @return Collection<int, Tag>
     */
    public function tagsFor(Model $model): Collection
    {
        return Tag::whereHas('taggables', fn ($q) => $q
            ->where('taggable_type', $model->getMorphClass())
            ->where('taggable_id', $model->getKey()))
            ->get();
    }

    /**
     * @return Collection<int, array{id: int, name: string, slug: string, count: int}>
     */
    public function userTags(int $userId): Collection
    {
        return Tag::where('user_id', $userId)
            ->withCount('taggables')
            ->orderBy('name')
            ->get();
    }

    private function slugify(string $name): string
    {
        $slug = str($name)->slug()->toString();

        return $slug === '' ? strtolower($name) : $slug;
    }
}
