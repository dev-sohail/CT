<?php

namespace App\Domains\DashboardAndWidgetFramework\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetRegistration extends Model
{
    protected $table = 'widget_registrations';

    protected $fillable = [
        'key',
        'title',
        'description',
        'category',
        'default_size_x',
        'default_size_y',
        'refresh_interval',
        'enabled',
    ];

    protected $casts = [
        'default_size_x' => 'integer',
        'default_size_y' => 'integer',
        'refresh_interval' => 'integer',
        'enabled' => 'boolean',
    ];
}