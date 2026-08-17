<?php

namespace App\Domains\QRAssetTaggingSystem\Http\Controllers;

use App\Domains\QRAssetTaggingSystem\Http\Resources\AssetResource;
use App\Domains\QRAssetTaggingSystem\Models\Asset;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends ApiController
{
    public function index(Request $request)
    {
        $items = Asset::forUser($request->user()->id)
            ->search($request->input('q'))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('location'), fn ($q, $l) => $q->where('location', $l))
            ->when($request->input('tag'), fn ($q, $t) => $q->whereJsonContains('tags', $t))
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 25));

        return $this->respondPaginated($items, AssetResource::class);
    }

    public function store(Request $request)
    {
        $data = $this->validateAsset($request);
        $item = Asset::create(array_merge([
            'user_id' => $request->user()->id,
            'qr_token' => Str::random(24),
        ], $data));

        return $this->respondCreated(AssetResource::make($item)->toArray($request));
    }

    public function show(Request $request, Asset $asset)
    {
        $this->authorizeOwner($request, $asset);

        return $this->respondSuccess(AssetResource::make($asset)->toArray($request));
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorizeOwner($request, $asset);
        $asset->update($this->validateAsset($request, true));

        return $this->respondSuccess(AssetResource::make($asset)->toArray($request));
    }

    public function destroy(Request $request, Asset $asset)
    {
        $this->authorizeOwner($request, $asset);
        $asset->delete();

        return $this->respondNoContent();
    }

    public function qr(Request $request, Asset $asset)
    {
        $this->authorizeOwner($request, $asset);

        return $this->respondSuccess([
            'id' => $asset->id,
            'payload' => $asset->qr_payload,
        ]);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'payload' => ['required', 'string'],
        ]);

        $token = preg_replace('#^ctlab://scan/asset/#', '', $request->input('payload'));
        $asset = Asset::forUser($request->user()->id)->where('qr_token', $token)->first();

        if (!$asset) {
            return $this->respondError('Asset not found.', 404);
        }

        if ($request->has('location')) {
            $asset->update(['location' => $request->input('location')]);
        }

        return $this->respondSuccess(AssetResource::make($asset)->toArray($request));
    }

    public function stats(Request $request)
    {
        $items = Asset::forUser($request->user()->id)->get();

        return $this->respondSuccess([
            'total' => $items->count(),
            'by_category' => $items->groupBy('category')->map->count(),
            'by_status' => $items->groupBy('status')->map->count(),
            'total_value' => round((float) $items->sum('value'), 2),
            'warranty_expiring_soon' => $items
                ->filter(fn (Asset $a) => $a->warranty_until && $a->warranty_until->between(now(), now()->addDays(30)))
                ->count(),
        ]);
    }

    private function validateAsset(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:128'],
            'status' => ['sometimes', 'in:in_place,missing,loaned,disposed'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'purchased_at' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }

    private function authorizeOwner(Request $request, Asset $asset): void
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}