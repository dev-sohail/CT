<?php

namespace App\Domains\DigitalMediaLibrary\Http\Controllers;

use App\Domains\DigitalMediaLibrary\Http\Resources\MediaItemResource;
use App\Domains\DigitalMediaLibrary\Models\MediaItem;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class MediaItemController extends ApiController
{
    public function index(Request $request)
    {
        $items = MediaItem::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, MediaItemResource::class);
    }

    public function store(Request $request)
    {
        $item = MediaItem::create(array_merge(['user_id' => $request->user()->id], $this->validateItem($request)));

        return $this->respondCreated(MediaItemResource::make($item)->toArray($request));
    }

    public function show(Request $request, MediaItem $mediaItem)
    {
        $this->authorizeOwner($request, $mediaItem);

        return $this->respondSuccess(MediaItemResource::make($mediaItem)->toArray($request));
    }

    public function update(Request $request, MediaItem $mediaItem)
    {
        $this->authorizeOwner($request, $mediaItem);
        $mediaItem->update($this->validateItem($request, true));

        return $this->respondSuccess(MediaItemResource::make($mediaItem)->toArray($request));
    }

    public function destroy(Request $request, MediaItem $mediaItem)
    {
        $this->authorizeOwner($request, $mediaItem);
        $mediaItem->delete();

        return $this->respondNoContent();
    }

    public function stats(Request $request)
    {
        $items = MediaItem::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $items->count(),
            'by_type' => $items->groupBy('type')->map->count(),
            'by_status' => $items->groupBy('status')->map->count(),
            'finished' => $items->where('status', 'finished')->count(),
            'average_rating' => $items->whereNotNull('rating')->isEmpty()
                ? null
                : round($items->whereNotNull('rating')->avg('rating'), 2),
        ]);
    }

    private function validateItem(Request $request, bool $partial = false): array
    {
        $rules = [
            'type' => [$partial ? 'sometimes' : 'required', 'in:book,movie,series,music,podcast,game,other'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'creator' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'between:1000,2100'],
            'genre' => ['nullable', 'string', 'max:128'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'rating' => ['nullable', 'integer', 'between:1,10'],
            'status' => ['sometimes', 'in:want,in_progress,finished,abandoned'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, MediaItem $item): void
    {
        if ($item->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}