<?php

namespace App\Domains\FileAndDocumentVault\Services;

use App\Domains\FileAndDocumentVault\Models\Document;
use App\Domains\FileAndDocumentVault\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentVaultService
{
    public function __construct(private readonly string $disk = 'local') {}

    public function store(int $userId, UploadedFile $file, array $attributes = []): Document
    {
        $name = $attributes['name'] ?? $file->getClientOriginalName();
        $checksum = hash_file('sha256', $file->getRealPath());
        $path = $file->store('documents', $this->disk);

        $document = Document::create([
            'user_id' => $userId,
            'name' => $name,
            'extension' => $file->getClientOriginalExtension() ?: null,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'folder' => $attributes['folder'] ?? null,
            'meta' => $attributes['meta'] ?? null,
            'current_version' => 1,
        ]);

        $this->recordVersion($document, $file, $path, $userId, 1);

        return $document;
    }

    public function storeNewVersion(int $userId, Document $document, UploadedFile $file): DocumentVersion
    {
        $path = $file->store('documents', $this->disk);
        $nextVersion = $document->current_version + 1;

        $version = $this->recordVersion($document, $file, $path, $userId, $nextVersion);

        $document->update([
            'name' => $file->getClientOriginalName() ?: $document->name,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'current_version' => $nextVersion,
        ]);

        return $version;
    }

    private function recordVersion(Document $document, UploadedFile $file, string $path, int $userId, int $version): DocumentVersion
    {
        return DocumentVersion::create([
            'document_id' => $document->id,
            'version' => $version,
            'disk' => $this->disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'created_by' => $userId,
        ]);
    }

    public function download(Document $document): array
    {
        $version = $document->currentFile();
        if (! $version) {
            abort(404);
        }

        return [
            'stream' => Storage::disk($version->disk)->readStream($version->path),
            'name' => $version->original_name ?: $document->name,
            'mime' => $version->mime_type,
        ];
    }
}
