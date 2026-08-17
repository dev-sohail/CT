<?php

namespace App\Domains\ScheduledTaskAndCronManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTaskLog extends Model
{
    protected $table = 'scheduled_task_logs';

    protected $fillable = [
        'scheduled_task_id',
        'status',
        'output',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class, 'scheduled_task_id');
    }
}