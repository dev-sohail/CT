<?php

namespace App\Domains\FileAutomationWatcher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileWatcherLog extends Model
{
    protected $table = 'file_watcher_logs';

    protected $fillable = [
        'file_watcher_rule_id',
        'filename',
        'status',
        'message',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FileWatcherRule::class, 'file_watcher_rule_id');
    }
}