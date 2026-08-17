<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\ReferenceRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\ReferenceResource;
use App\Domains\SecondBrainPersonalWiki\Models\Reference;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ReferenceController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Reference::query()
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->string('type'));
        }

        return $this->respondSuccess(ReferenceResource::collection($query->orderByDesc('updated_at')->get()));
    }

    public function store(ReferenceRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        return $this->respondCreated(ReferenceResource::make($workspace->references()->create($request->validated())));
    }

    public function show(Request $request, Reference $reference)
    {
        $this->scope->assertOwns($reference, ['workspace'], $request->user()->id);

        return $this->respondSuccess(ReferenceResource::make($reference));
    }

    public function update(ReferenceRequest $request, Reference $reference)
    {
        $this->scope->assertOwns($reference, ['workspace'], $request->user()->id);

        $reference->update($request->validated());

        return $this->respondSuccess(ReferenceResource::make($reference->fresh()));
    }

    public function destroy(Request $request, Reference $reference)
    {
        $this->scope->assertOwns($reference, ['workspace'], $request->user()->id);

        $reference->delete();

        return $this->respondNoContent();
    }
}
