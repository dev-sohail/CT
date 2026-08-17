<?php

namespace App\Domains\ImportExportAndDataPortability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $table = 'imports';

    protected $fillable = [
        'user_id',
        'domain',
        'format',
        'status',
        'item_counts',
        'errors',
    ];

    protected $casts = [
        'item_counts' => 'array',
        'errors' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }
}