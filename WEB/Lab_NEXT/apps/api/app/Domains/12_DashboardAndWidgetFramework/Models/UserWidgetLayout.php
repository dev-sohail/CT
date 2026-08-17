<?php

namespace App\Domains\DashboardAndWidgetFramework\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWidgetLayout extends Model
{
    protected $table = 'user_widget_layout';

    protected $fillable = [
        'user_id',
        'widget_key',
        'position_x',
        'position_y',
        'width',
        'height',
        'enabled',
        'refresh_interval',
    ];

    protected $casts = [
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'enabled' => 'boolean',
        'refresh_interval' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }
}