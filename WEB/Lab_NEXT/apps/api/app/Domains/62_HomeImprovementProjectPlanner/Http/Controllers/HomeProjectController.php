<?php

namespace App\Domains\HomeImprovementProjectPlanner\Http\Controllers;

use App\Domains\HomeImprovementProjectPlanner\Http\Resources\HomeProjectResource;
use App\Domains\HomeImprovementProjectPlanner\Models\HomeProject;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class HomeProjectController extends ApiController
{
    public function index(Request $request)
    {
        $items = HomeProject::forUser($request->user()->id)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->when($request->input('room'), fn ($q, $r) => $q->where('room', $r))->orderBy('target_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, HomeProjectResource::class);
    }
    public function store(Request $request) { $project = HomeProject::create(array_merge(['user_id' => $request->user()->id], $this->validateProject($request))); return $this->respondCreated(HomeProjectResource::make($project)->toArray($request)); }
    public function show(Request $request, HomeProject $homeProject) { $this->authorizeOwner($request, $homeProject); return $this->respondSuccess(HomeProjectResource::make($homeProject)->toArray($request)); }
    public function update(Request $request, HomeProject $homeProject) { $this->authorizeOwner($request, $homeProject); $homeProject->update($this->validateProject($request, true)); return $this->respondSuccess(HomeProjectResource::make($homeProject)->toArray($request)); }
    public function destroy(Request $request, HomeProject $homeProject) { $this->authorizeOwner($request, $homeProject); $homeProject->delete(); return $this->respondNoContent(); }
    public function complete(Request $request, HomeProject $homeProject) { $this->authorizeOwner($request, $homeProject); $homeProject->update(['status' => 'completed', 'completed_on' => now()->toDateString()]); return $this->respondSuccess(HomeProjectResource::make($homeProject)->toArray($request)); }
    public function stats(Request $request) { $items = HomeProject::forUser($request->user()->id)->get(); return $this->respondSuccess(['total' => $items->count(), 'completed' => $items->where('status', 'completed')->count(), 'active' => $items->whereIn('status', ['planned', 'in_progress'])->count(), 'budget' => (float) $items->sum('budget'), 'spent' => (float) $items->sum('spent')]); }
    private function validateProject(Request $request, bool $partial = false): array { return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'room' => ['nullable', 'string', 'max:128'], 'status' => ['sometimes', 'in:planned,in_progress,completed,on_hold'], 'priority' => ['sometimes', 'in:low,medium,high'], 'started_on' => ['nullable', 'date'], 'target_date' => ['nullable', 'date'], 'completed_on' => ['nullable', 'date'], 'budget' => ['nullable', 'numeric', 'min:0'], 'spent' => ['sometimes', 'numeric', 'min:0'], 'tasks' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]); }
    private function authorizeOwner(Request $request, HomeProject $project): void { if ($project->user_id !== $request->user()->id) abort(404); }
}
