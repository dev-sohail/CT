<?php

namespace App\Domains\SecondBrainPersonalWiki\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'wiki_templates';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];
}
