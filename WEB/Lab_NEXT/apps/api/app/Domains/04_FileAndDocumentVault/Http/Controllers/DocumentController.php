<?php

namespace App\Domains\FileAndDocumentVault\Http\Controllers;

use App\Domains\FileAndDocumentVault\Http\Resources\DocumentResource;
use App\Domains\FileAndDocumentVault\Models\Document;
use App\Domains\FileAndDocumentVault\Services\DocumentVaultService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class DocumentController extends ApiController
{
    public function __construct(private readonly DocumentVaultService $vault) {}

    public function index(Request $request)
    {
        $documents = Document::where('user_id', $request->user()->id)
            ->when($request->has('folder'), fn ($q) => $q->where('folder', $request->string('folder')))
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($documents, DocumentResource::class);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'name' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $document = $this->vault->store(
            $request->user()->id,
            $request->file('file'),
            $request->only(['name', 'folder', 'meta'])
        );

        return $this->respondCreated(DocumentResource::make($document)->toArray($request));
    }

    public function show(Request $request, Document $document)
    {
        $this->authorizeOwner($request, $document);

        return $this->respondSuccess(DocumentResource::make($document)->toArray($request));
    }

    public function download(Request $request, Document $document)
    {
        $this->authorizeOwner($request, $document);
        $file = $this->vault->download($document);

        return response()->streamDownload(function () use ($file) {
            fpassthru($file['stream']);
        }, $file['name'], ['Content-Type' => $file['mime'] ?? 'application/octet-stream']);
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeOwner($request, $document);
        $document->delete();

        return $this->respondNoContent();
    }

    public function restore(Request $request, int $documentId)
    {
        $document = Document::withTrashed()->findOrFail($documentId);

        if ($document->user_id !== $request->user()->id) {
            return $this->respondError('Document not found', 404);
        }
        $document->restore();

        return $this->respondSuccess(DocumentResource::make($document)->toArray($request));
    }

    private function authorizeOwner(Request $request, Document $document): void
    {
        if ($document->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
