<?php

namespace App\Domains\SecondBrainPersonalWiki\Http\Controllers;

use App\Domains\SecondBrainPersonalWiki\Http\Requests\WorkspaceRequest;
use App\Domains\SecondBrainPersonalWiki\Http\Resources\WorkspaceResource;
use App\Domains\SecondBrainPersonalWiki\Models\Workspace;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class WorkspaceController extends ApiController
{
    public function index(Request $request)
    {
        $workspaces = Workspace::where('user_id', $request->user()->id)
            ->with('notebooks')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->respondSuccess(WorkspaceResource::collection($workspaces));
    }

    public function store(WorkspaceRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $workspace = Workspace::create($data);
        $workspace->load('notebooks');

        return $this->respondCreated(WorkspaceResource::make($workspace));
    }

    public function show(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->user_id === $request->user()->id, 403, 'Not authorized.');

        $workspace->load(['notebooks.sections']);

        return $this->respondSuccess(WorkspaceResource::make($workspace));
    }

    public function update(WorkspaceRequest $request, Workspace $workspace)
    {
        abort_unless($workspace->user_id === $request->user()->id, 403, 'Not authorized.');

        $workspace->update($request->validated());

        return $this->respondSuccess(WorkspaceResource::make($workspace->fresh('notebooks')));
    }

    public function destroy(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->user_id === $request->user()->id, 403, 'Not authorized.');

        $workspace->delete();

        return $this->respondNoContent();
    }
}
