<?php

namespace App\Domains\ProjectRoadmapPlanner\Http\Controllers;

use App\Domains\ProjectRoadmapPlanner\Http\Resources\ProjectMilestoneResource;
use App\Domains\ProjectRoadmapPlanner\Http\Resources\ProjectResource;
use App\Domains\ProjectRoadmapPlanner\Models\Project;
use App\Domains\ProjectRoadmapPlanner\Models\ProjectMilestone;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    public function index(Request $request)
    {
        $projects = Project::forUser($request->user()->id)
            ->with('milestones')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('priority', 'desc')
            ->orderBy('target_end_at')
            ->get();

        return $this->respondSuccess(ProjectResource::collection($projects));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProject($request);

        $project = Project::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));

        return $this->respondCreated(ProjectResource::make($project));
    }

    public function show(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return $this->respondError('Project not found.', 404);
        }
        return $this->respondSuccess(ProjectResource::make($project->load('milestones')));
    }

    public function update(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return $this->respondError('Project not found.', 404);
        }
        $project->update($this->validateProject($request, true));
        return $this->respondSuccess(ProjectResource::make($project->fresh('milestones')));
    }

    public function destroy(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return $this->respondError('Project not found.', 404);
        }
        $project->delete();
        return $this->respondNoContent();
    }

    public function timeline(Request $request)
    {
        $projects = Project::forUser($request->user()->id)
            ->where('status', '!=', 'backlog')
            ->with('milestones')
            ->get();

        $rows = $projects->map(fn (Project $p) => [
            'project' => ProjectResource::make($p),
            'start_at' => $p->start_at?->toIso8601String(),
            'end_at' => $p->target_end_at?->toIso8601String(),
            'milestones' => $p->milestones->map(fn (ProjectMilestone $m) => [
                'title' => $m->title,
                'due_at' => $m->due_at?->toIso8601String(),
                'status' => $m->status,
            ])->values(),
        ])->values();

        return $this->respondSuccess([
            'rows' => $rows,
            'earliest' => $projects->min('start_at')?->toIso8601String(),
            'latest' => $projects->max('target_end_at')?->toIso8601String(),
        ]);
    }

    // ------------------------------------------------------------------
    // Milestones
    // ------------------------------------------------------------------

    public function milestones(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return $this->respondError('Project not found.', 404);
        }
        return $this->respondSuccess(ProjectMilestoneResource::collection($project->milestones()->get()));
    }

    public function addMilestone(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            return $this->respondError('Project not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $count = $project->milestones()->count();
        $milestone = $project->milestones()->create(array_merge(
            $validated,
            ['sort_order' => $validated['sort_order'] ?? $count]
        ));

        return $this->respondCreated(ProjectMilestoneResource::make($milestone));
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone)
    {
        if ($project->user_id !== $request->user()->id || $milestone->project_id !== $project->id) {
            return $this->respondError('Milestone not found.', 404);
        }

        $milestone->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'sort_order' => ['nullable', 'integer'],
        ]));

        return $this->respondSuccess(ProjectMilestoneResource::make($milestone));
    }

    public function deleteMilestone(Request $request, Project $project, ProjectMilestone $milestone)
    {
        if ($project->user_id !== $request->user()->id || $milestone->project_id !== $project->id) {
            return $this->respondError('Milestone not found.', 404);
        }
        $milestone->delete();
        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validateProject(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:backlog,planned,in_progress,on_hold,completed'],
            'start_at' => ['nullable', 'date'],
            'target_end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'client' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}