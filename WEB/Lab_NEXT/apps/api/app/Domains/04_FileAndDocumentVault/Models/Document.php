<?php

namespace App\Domains\FileAndDocumentVault\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'user_id',
        'name',
        'extension',
        'mime_type',
        'size',
        'checksum',
        'folder',
        'meta',
        'current_version',
    ];

    protected $casts = [
        'size' => 'integer',
        'current_version' => 'integer',
        'meta' => 'json',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    public function currentFile(): ?DocumentVersion
    {
        return $this->versions()->where('version', $this->current_version)->first();
    }
}
