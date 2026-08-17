<?php

namespace App\Domains\ImportExportAndDataPortability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Export extends Model
{
    protected $table = 'exports';

    protected $fillable = [
        'user_id',
        'status',
        'format',
        'domains',
        'file_path',
        'item_counts',
        'error',
    ];

    protected $casts = [
        'domains' => 'array',
        'item_counts' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\CoreIdentityAndAccessKernel\Models\User::class, 'user_id');
    }

    public function downloadStream()
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk('local')->readStream($this->file_path);
    }

    public function downloadSize(): ?int
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk('local')->size($this->file_path);
    }

    public function downloadName(): string
    {
        return 'ctlab-export-' . $this->created_at->format('Ymd-His') . '.' . $this->format;
    }
}