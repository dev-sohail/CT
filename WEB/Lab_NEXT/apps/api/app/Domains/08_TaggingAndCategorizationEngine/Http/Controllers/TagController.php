<?php

namespace App\Domains\TaggingAndCategorizationEngine\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\TaggingAndCategorizationEngine\Http\Resources\TagResource;
use App\Domains\TaggingAndCategorizationEngine\Models\Tag;
use App\Domains\TaggingAndCategorizationEngine\Services\TagService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TagController extends ApiController
{
    public function __construct(private readonly TagService $tags) {}

    public function index(Request $request)
    {
        $tags = $this->tags->userTags($request->user()->id);

        return $this->respondSuccess(TagResource::collection($tags));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
        ]);

        $tag = $this->tags->ensureTag($request->user()->id, $data['name'], $data['color'] ?? null);
        $tag->save();

        return $this->respondCreated(TagResource::make($tag)->toArray($request));
    }

    public function update(Request $request, Tag $tag)
    {
        if ($tag->user_id !== $request->user()->id) {
            return $this->respondError('Tag not found', 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
        ]);

        $tag->fill(['name' => $data['name'], 'color' => $data['color'] ?? $tag->color])->save();

        return $this->respondSuccess(TagResource::make($tag)->toArray($request));
    }

    public function destroy(Request $request, Tag $tag)
    {
        if ($tag->user_id !== $request->user()->id) {
            return $this->respondError('Tag not found', 404);
        }
        $tag->delete();

        return $this->respondNoContent();
    }

    public function attach(Request $request)
    {
        $data = $request->validate([
            'taggable_type' => ['required', 'string'],
            'taggable_id' => ['required', 'integer'],
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['string', 'max:100'],
        ]);

        $model = $this->resolveTaggable($data['taggable_type'], $data['taggable_id'], $request->user()->id);

        $this->tags->attach($request->user()->id, $model, $data['names']);

        return $this->respondSuccess(TagResource::collection($this->tags->tagsFor($model)));
    }

    public function detach(Request $request)
    {
        $data = $request->validate([
            'taggable_type' => ['required', 'string'],
            'taggable_id' => ['required', 'integer'],
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['string', 'max:100'],
        ]);

        $model = $this->resolveTaggable($data['taggable_type'], $data['taggable_id'], $request->user()->id);

        $this->tags->detach($request->user()->id, $model, $data['names']);

        return $this->respondSuccess(TagResource::collection($this->tags->tagsFor($model)));
    }

    private function resolveTaggable(string $type, int $id, int $userId): Model
    {
        if (! class_exists($type)) {
            abort(404, 'Unknown taggable type');
        }

        $model = $type::find($id);
        if (! $model) {
            abort(404);
        }

        $ownerId = method_exists($model, 'searchOwnerId') ? $model->searchOwnerId() : null;
        if ($ownerId !== null && $ownerId !== $userId) {
            abort(404);
        }

        return $model;
    }
}
