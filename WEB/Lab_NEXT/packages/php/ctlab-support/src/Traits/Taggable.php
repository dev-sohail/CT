<?php

namespace Ctlab\Support\Traits;

trait Taggable
{
    public function tagged()
    {
        return $this->morphMany(\App\Domains\Tags\Models\Taggable::class, 'taggable');
    }
}
