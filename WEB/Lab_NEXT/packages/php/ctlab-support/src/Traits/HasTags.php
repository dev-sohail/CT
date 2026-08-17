<?php

namespace Ctlab\Support\Traits;

use App\Domains\Tags\Models\Tag;

trait HasTags
{
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables')
            ->withTimestamps();
    }
}
