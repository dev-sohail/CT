<?php

namespace App\Domains\FileAutomationWatcher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileWatcherRule extends Model
{
    protected $table = 'file_watcher_rules';

    protected $fillable = [
        'user_id',
        'name',
        'source_disk',
        'source_path',
        'pattern',
        'action',
        'destination_path',
        'tag_keyword',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FileWatcherLog::class, 'file_watcher_rule_id')->orderByDesc('created_at');
    }
}