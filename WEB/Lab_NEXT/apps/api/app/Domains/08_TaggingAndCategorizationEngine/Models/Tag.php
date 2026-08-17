<?php

namespace App\Domains\TaggingAndCategorizationEngine\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            $tag->slug = Str::slug($tag->name) ?: Str::lower($tag->name);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function taggables(): HasMany
    {
        return $this->hasMany(Taggable::class);
    }
}
