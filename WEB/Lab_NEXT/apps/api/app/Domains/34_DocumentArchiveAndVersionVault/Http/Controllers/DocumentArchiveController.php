<?php

namespace App\Domains\DocumentArchiveAndVersionVault\Http\Controllers;

use App\Domains\DocumentArchiveAndVersionVault\Http\Resources\DocumentArchiveResource;
use App\Domains\DocumentArchiveAndVersionVault\Models\DocumentArchive;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class DocumentArchiveController extends ApiController
{
    public function index(Request $request)
    {
        $items = DocumentArchive::forUser($request->user()->id)
            ->with('document')
            ->search($request->input('q'))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, DocumentArchiveResource::class);
    }

    public function store(Request $request)
    {
        $data = $this->validateArchive($request);
        $item = DocumentArchive::create(array_merge(['user_id' => $request->user()->id], $data));

        return $this->respondCreated(DocumentArchiveResource::make($item)->toArray($request));
    }

    public function show(Request $request, DocumentArchive $archive)
    {
        $this->authorizeOwner($request, $archive);

        return $this->respondSuccess(DocumentArchiveResource::make($archive)->toArray($request));
    }

    public function update(Request $request, DocumentArchive $archive)
    {
        $this->authorizeOwner($request, $archive);
        $archive->update($this->validateArchive($request, true));

        return $this->respondSuccess(DocumentArchiveResource::make($archive)->toArray($request));
    }

    public function destroy(Request $request, DocumentArchive $archive)
    {
        $this->authorizeOwner($request, $archive);
        $archive->delete();

        return $this->respondNoContent();
    }

    public function stats(Request $request)
    {
        $items = DocumentArchive::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $items->count(),
            'by_category' => $items->groupBy('category')->map->count(),
            'active' => $items->where('status', 'active')->count(),
            'expired' => $items->where('status', 'expired')->count(),
            'expiring_soon' => $items
                ->filter(fn (DocumentArchive $a) => $a->expires_at && $a->expires_at->between(now(), now()->addDays(30)))
                ->count(),
        ]);
    }

    private function validateArchive(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'source' => ['nullable', 'string', 'max:128'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:active,archived,expired'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, DocumentArchive $archive): void
    {
        if ($archive->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}