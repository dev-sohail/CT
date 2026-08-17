<?php

namespace App\Domains\FileAndDocumentVault\Http\Controllers;

use App\Domains\FileAndDocumentVault\Http\Resources\DocumentResource;
use App\Domains\FileAndDocumentVault\Models\Document;
use App\Domains\FileAndDocumentVault\Services\DocumentVaultService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class DocumentVersionController extends ApiController
{
    public function __construct(private readonly DocumentVaultService $vault) {}

    public function index(Request $request, Document $document)
    {
        if ($document->user_id !== $request->user()->id) {
            return $this->respondError('Document not found', 404);
        }

        $versions = $document->versions()->get();

        return $this->respondSuccess($versions->map(fn ($version) => [
            'version' => $version->version,
            'original_name' => $version->original_name,
            'mime_type' => $version->mime_type,
            'size' => $version->size,
            'created_at' => $version->created_at,
        ]));
    }

    public function store(Request $request, Document $document)
    {
        if ($document->user_id !== $request->user()->id) {
            return $this->respondError('Document not found', 404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $version = $this->vault->storeNewVersion($request->user()->id, $document, $request->file('file'));

        return $this->respondCreated(
            DocumentResource::make($document->fresh())->toArray($request),
            ['version' => $version->version]
        );
    }
}
