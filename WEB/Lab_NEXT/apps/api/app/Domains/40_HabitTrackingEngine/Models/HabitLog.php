<?php

namespace App\Domains\HabitTrackingEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitLog extends Model
{
    protected $fillable = ['habit_id', 'user_id', 'logged_for', 'count', 'completed', 'notes'];

    protected $casts = [
        'logged_for' => 'date',
        'count' => 'integer',
        'completed' => 'boolean',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
