<?php

namespace App\Domains\ScheduledTaskAndCronManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledTask extends Model
{
    protected $table = 'scheduled_tasks';

    protected $fillable = [
        'user_id',
        'name',
        'command',
        'cron_expression',
        'is_active',
        'arguments',
        'last_status',
        'last_output',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'arguments' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ScheduledTaskLog::class, 'scheduled_task_id')->orderByDesc('created_at');
    }
}