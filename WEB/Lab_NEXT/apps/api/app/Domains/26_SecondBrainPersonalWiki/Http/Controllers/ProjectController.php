<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\ProjectRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\PageResource;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\ProjectResource;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Project;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Project::query()
            ->with('pages')
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }

        return $this->respondSuccess(ProjectResource::collection($query->orderBy('sort_order')->get()));
    }

    public function store(ProjectRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        $project = $workspace->projects()->create($request->validated());

        return $this->respondCreated(ProjectResource::make($project->fresh('pages')));
    }

    public function show(Request $request, Project $project)
    {
        $this->scope->assertOwns($project, ['workspace'], $request->user()->id);

        $project->load('pages');

        return $this->respondSuccess(ProjectResource::make($project));
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $this->scope->assertOwns($project, ['workspace'], $request->user()->id);

        $project->update($request->validated());

        return $this->respondSuccess(ProjectResource::make($project->fresh('pages')));
    }

    public function destroy(Request $request, Project $project)
    {
        $this->scope->assertOwns($project, ['workspace'], $request->user()->id);

        $project->delete();

        return $this->respondNoContent();
    }

    public function attachPage(Request $request, Project $project, Page $page)
    {
        $this->scope->assertOwns($project, ['workspace'], $request->user()->id);
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        $page->project_id = $project->id;
        $page->save();

        return $this->respondSuccess(PageResource::make($page->fresh()));
    }

    public function detachPage(Request $request, Project $project, Page $page)
    {
        $this->scope->assertOwns($project, ['workspace'], $request->user()->id);
        $this->scope->assertOwns($page, ['section', 'notebook', 'workspace'], $request->user()->id);

        if ($page->project_id === $project->id) {
            $page->project_id = null;
            $page->save();
        }

        return $this->respondSuccess(PageResource::make($page->fresh()));
    }
}
