<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $table = 'wiki_workspaces';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'color',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notebooks(): HasMany
    {
        return $this->hasMany(Notebook::class)->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
