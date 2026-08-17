<?php

namespace App\Domains\FileAndDocumentVault\Models;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'version',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'size' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
