<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\NotebookRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\NotebookResource;
use App\Domains\SecondBrainPersonalWiki\Models\Notebook;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class NotebookController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Notebook::query()
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('sections');

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }

        return $this->respondSuccess(NotebookResource::collection($query->get()));
    }

    public function store(NotebookRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        $notebook = $workspace->notebooks()->create($request->validated());

        return $this->respondCreated(NotebookResource::make($notebook->fresh('sections')));
    }

    public function show(Request $request, Notebook $notebook)
    {
        $this->scope->assertOwns($notebook, ['workspace'], $request->user()->id);

        $notebook->load('sections.pages');

        return $this->respondSuccess(NotebookResource::make($notebook));
    }

    public function update(NotebookRequest $request, Notebook $notebook)
    {
        $this->scope->assertOwns($notebook, ['workspace'], $request->user()->id);

        $notebook->update($request->validated());

        return $this->respondSuccess(NotebookResource::make($notebook->fresh('sections')));
    }

    public function destroy(Request $request, Notebook $notebook)
    {
        $this->scope->assertOwns($notebook, ['workspace'], $request->user()->id);

        $notebook->delete();

        return $this->respondNoContent();
    }
}
