<?php

namespace App\Domains\BookmarkAndReadLaterArchive\Http\Controllers;

use App\Domains\BookmarkAndReadLaterArchive\Http\Resources\BookmarkResource;
use App\Domains\BookmarkAndReadLaterArchive\Models\Bookmark;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class BookmarkController extends ApiController
{
    public function index(Request $request)
    {
        $bookmarks = Bookmark::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->orderByDesc('updated_at')
            ->get();

        return $this->respondSuccess(BookmarkResource::collection($bookmarks));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'saved_content' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:unread,reading,read,archived'],
            'favicon_url' => ['nullable', 'url', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validated['domain'] = parse_url($validated['url'], PHP_URL_HOST);

        $bookmark = Bookmark::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));

        return $this->respondCreated(BookmarkResource::make($bookmark));
    }

    public function show(Request $request, Bookmark $bookmark)
    {
        if ($bookmark->user_id !== $request->user()->id) {
            return $this->respondError('Bookmark not found.', 404);
        }
        return $this->respondSuccess(BookmarkResource::make($bookmark));
    }

    public function update(Request $request, Bookmark $bookmark)
    {
        if ($bookmark->user_id !== $request->user()->id) {
            return $this->respondError('Bookmark not found.', 404);
        }

        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:2048'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'saved_content' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:unread,reading,read,archived'],
            'favicon_url' => ['nullable', 'url', 'max:2048'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (isset($validated['url'])) {
            $validated['domain'] = parse_url($validated['url'], PHP_URL_HOST);
        }

        $bookmark->update($validated);

        return $this->respondSuccess(BookmarkResource::make($bookmark));
    }

    public function destroy(Request $request, Bookmark $bookmark)
    {
        if ($bookmark->user_id !== $request->user()->id) {
            return $this->respondError('Bookmark not found.', 404);
        }
        $bookmark->delete();
        return $this->respondNoContent();
    }

    public function stats(Request $request)
    {
        $bookmarks = Bookmark::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $bookmarks->count(),
            'by_status' => $bookmarks->groupBy('status')->map->count(),
            'by_domain' => $bookmarks->groupBy('domain')->map->count()->sortDesc()->take(10),
            'unread' => $bookmarks->where('status', 'unread')->count(),
        ]);
    }
}