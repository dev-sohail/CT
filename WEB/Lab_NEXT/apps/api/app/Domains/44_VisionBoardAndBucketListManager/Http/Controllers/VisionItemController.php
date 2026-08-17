<?php

namespace App\Domains\VisionBoardAndBucketListManager\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\VisionBoardAndBucketListManager\Http\Resources\VisionItemResource;
use App\Domains\VisionBoardAndBucketListManager\Models\VisionItem;
use Illuminate\Http\Request;

class VisionItemController extends ApiController
{
    public function index(Request $request)
    {
        $items = VisionItem::forUser($request->user()->id)
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('category'), fn ($q, $category) => $q->where('category', $category))
            ->when($request->input('tag'), fn ($q, $tag) => $q->whereJsonContains('tags', $tag))
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('target_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, VisionItemResource::class);
    }

    public function store(Request $request)
    {
        $item = VisionItem::create(array_merge(['user_id' => $request->user()->id], $this->validateItem($request)));
        return $this->respondCreated(VisionItemResource::make($item)->toArray($request));
    }

    public function show(Request $request, VisionItem $visionItem)
    {
        $this->authorizeOwner($request, $visionItem);
        return $this->respondSuccess(VisionItemResource::make($visionItem)->toArray($request));
    }

    public function update(Request $request, VisionItem $visionItem)
    {
        $this->authorizeOwner($request, $visionItem);
        $visionItem->update($this->validateItem($request, true));
        return $this->respondSuccess(VisionItemResource::make($visionItem)->toArray($request));
    }

    public function destroy(Request $request, VisionItem $visionItem)
    {
        $this->authorizeOwner($request, $visionItem);
        $visionItem->delete();
        return $this->respondNoContent();
    }

    public function complete(Request $request, VisionItem $visionItem)
    {
        $this->authorizeOwner($request, $visionItem);
        $completed = $visionItem->status !== 'completed';
        $visionItem->update(['status' => $completed ? 'completed' : 'active', 'completed_at' => $completed ? now() : null]);
        return $this->respondSuccess(VisionItemResource::make($visionItem)->toArray($request));
    }

    public function board(Request $request)
    {
        $items = VisionItem::forUser($request->user()->id)->get();
        return $this->respondSuccess($items->groupBy(fn (VisionItem $item) => $item->category ?: 'uncategorized')->map(fn ($group) => VisionItemResource::collection($group)));
    }

    public function stats(Request $request)
    {
        $items = VisionItem::forUser($request->user()->id)->get();
        return $this->respondSuccess([
            'total' => $items->count(),
            'completed' => $items->where('status', 'completed')->count(),
            'active' => $items->where('status', 'active')->count(),
            'by_type' => $items->groupBy('type')->map->count(),
            'by_category' => $items->groupBy('category')->map->count(),
            'completion_rate' => $items->isEmpty() ? 0 : round($items->where('status', 'completed')->count() / $items->count() * 100, 1),
        ]);
    }

    private function validateItem(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:vision,bucket_list'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'in:active,completed,paused'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'target_date' => ['nullable', 'date'],
            'image_url' => ['nullable', 'url', 'max:1024'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function authorizeOwner(Request $request, VisionItem $item): void
    {
        if ($item->user_id !== $request->user()->id) abort(404);
    }
}
