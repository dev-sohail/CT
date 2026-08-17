<?php

namespace App\Domains\CodeSnippetManagerAndPackageIndex\Http\Controllers;

use App\Domains\CodeSnippetManagerAndPackageIndex\Http\Resources\CodeSnippetResource;
use App\Domains\CodeSnippetManagerAndPackageIndex\Models\CodeSnippet;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class CodeSnippetController extends ApiController
{
    public function index(Request $request)
    {
        $snippets = CodeSnippet::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('language'), fn ($q, $l) => $q->where('language', $l))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->when($request->boolean('favorites'), fn ($q) => $q->where('favorite', true))
            ->orderByDesc('updated_at')
            ->get();

        return $this->respondSuccess(CodeSnippetResource::collection($snippets));
    }

    public function store(Request $request)
    {
        $snippet = CodeSnippet::create(array_merge(
            ['user_id' => $request->user()->id],
            $this->validateSnippet($request)
        ));

        return $this->respondCreated(CodeSnippetResource::make($snippet));
    }

    public function show(Request $request, CodeSnippet $snippet)
    {
        if ($snippet->user_id !== $request->user()->id) {
            return $this->respondError('Snippet not found.', 404);
        }
        return $this->respondSuccess(CodeSnippetResource::make($snippet));
    }

    public function update(Request $request, CodeSnippet $snippet)
    {
        if ($snippet->user_id !== $request->user()->id) {
            return $this->respondError('Snippet not found.', 404);
        }
        $snippet->update($this->validateSnippet($request, true));
        return $this->respondSuccess(CodeSnippetResource::make($snippet));
    }

    public function destroy(Request $request, CodeSnippet $snippet)
    {
        if ($snippet->user_id !== $request->user()->id) {
            return $this->respondError('Snippet not found.', 404);
        }
        $snippet->delete();
        return $this->respondNoContent();
    }

    public function toggleFavorite(Request $request, CodeSnippet $snippet)
    {
        if ($snippet->user_id !== $request->user()->id) {
            return $this->respondError('Snippet not found.', 404);
        }
        $snippet->update(['favorite' => !$snippet->favorite]);
        return $this->respondSuccess(CodeSnippetResource::make($snippet));
    }

    public function packageIndex(Request $request)
    {
        $type = $request->input('type'); // composer|npm
        $snippets = CodeSnippet::forUser($request->user()->id)
            ->whereNotNull('package_name')
            ->when($type, fn ($q, $t) => $q->where('package_type', $t))
            ->orderBy('package_name')
            ->get()
            ->map(fn (CodeSnippet $s) => [
                'name' => $s->package_name,
                'type' => $s->package_type,
                'version' => $s->package_version,
                'snippet_id' => $s->id,
                'language' => $s->language,
                'tags' => $s->tags,
            ]);

        return $this->respondSuccess($snippets->values());
    }

    public function stats(Request $request)
    {
        $snippets = CodeSnippet::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $snippets->count(),
            'favorites' => $snippets->where('favorite', true)->count(),
            'by_language' => $snippets->groupBy('language')->map->count(),
            'packages' => $snippets->whereNotNull('package_name')->count(),
        ]);
    }

    private function validateSnippet(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['sometimes', 'string', 'max:32'],
            'code' => [$partial ? 'sometimes' : 'required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'package_type' => ['nullable', 'in:composer,npm'],
            'package_name' => ['nullable', 'string', 'max:255'],
            'package_version' => ['nullable', 'string', 'max:64'],
            'favorite' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}