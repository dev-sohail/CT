<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\TaskRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\TaskResource;
use App\Domains\SecondBrainPersonalWiki\Models\Page;
use App\Domains\SecondBrainPersonalWiki\Models\Task;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SecondBrainPersonalWiki\Services\WorkspaceScope;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function __construct(private readonly WorkspaceScope $scope) {}

    public function index(Request $request, ?Workspace $workspace = null)
    {
        $query = Task::query()
            ->with('taskable')
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $request->user()->id));

        if ($workspace?->exists) {
            $query->where('workspace_id', $workspace->id);
        }
        if ($request->has('done')) {
            $query->where('done', $request->boolean('done'));
        }

        return $this->respondSuccess(TaskResource::collection($query->orderBy('done')->orderByDesc('updated_at')->get()));
    }

    public function store(TaskRequest $request, Workspace $workspace)
    {
        $this->scope->assertOwns($workspace, [], $request->user()->id);

        $data = $request->validated();
        if ($linkPageId = $data['link_page_id'] ?? null) {
            $data['taskable_type'] = Page::class;
            $data['taskable_id'] = $linkPageId;
        }
        unset($data['link_page_id']);

        return $this->respondCreated(TaskResource::make($workspace->tasks()->create($data)));
    }

    public function show(Request $request, Task $task)
    {
        $this->scope->assertOwns($task, ['workspace'], $request->user()->id);

        return $this->respondSuccess(TaskResource::make($task->load('taskable')));
    }

    public function update(TaskRequest $request, Task $task)
    {
        $this->scope->assertOwns($task, ['workspace'], $request->user()->id);

        $data = $request->validated();
        if (array_key_exists('link_page_id', $data)) {
            $data['taskable_type'] = $data['link_page_id'] ? Page::class : null;
            $data['taskable_id'] = $data['link_page_id'] ?? null;
        }
        unset($data['link_page_id']);

        $task->update($data);

        return $this->respondSuccess(TaskResource::make($task->fresh('taskable')));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->scope->assertOwns($task, ['workspace'], $request->user()->id);

        $task->delete();

        return $this->respondNoContent();
    }
}
