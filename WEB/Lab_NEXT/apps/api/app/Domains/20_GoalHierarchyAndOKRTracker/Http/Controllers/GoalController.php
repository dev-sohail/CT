<?php

namespace App\Domains\GoalHierarchyAndOKRTracker\Http\Controllers;

use App\Domains\GoalHierarchyAndOKRTracker\Http\Resources\GoalResource;
use App\Domains\GoalHierarchyAndOKRTracker\Http\Resources\KeyResultResource;
use App\Domains\GoalHierarchyAndOKRTracker\Models\Goal;
use App\Domains\GoalHierarchyAndOKRTracker\Models\KeyResult;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class GoalController extends ApiController
{
    public function index(Request $request)
    {
        $goals = Goal::forUser($request->user()->id)
            ->with(['keyResults', 'children'])
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();

        return $this->respondSuccess(GoalResource::collection($goals));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'level' => ['sometimes', 'in:life,goal,year,quarter,week'],
            'parent_id' => ['nullable', 'exists:goals,id'],
            'start_at' => ['nullable', 'date'],
            'target_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:active,completed,paused,abandoned'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $goal = Goal::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));
        $this->rollupAncestors($goal);

        return $this->respondCreated(GoalResource::make($goal->load('keyResults')));
    }

    public function show(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return $this->respondError('Goal not found.', 404);
        }
        return $this->respondSuccess(GoalResource::make($goal->load(['keyResults', 'children'])));
    }

    public function update(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return $this->respondError('Goal not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'level' => ['sometimes', 'in:life,goal,year,quarter,week'],
            'parent_id' => ['nullable', 'exists:goals,id'],
            'start_at' => ['nullable', 'date'],
            'target_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:active,completed,paused,abandoned'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ]);

        $goal->update($validated);
        $this->rollupAncestors($goal);

        return $this->respondSuccess(GoalResource::make($goal->fresh(['keyResults'])));
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return $this->respondError('Goal not found.', 404);
        }
        $goal->delete();
        return $this->respondNoContent();
    }

    public function tree(Request $request)
    {
        $goals = Goal::forUser($request->user()->id)
            ->with(['keyResults', 'children'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return $this->respondSuccess(GoalResource::collection($goals));
    }

    // ------------------------------------------------------------------
    // Key Results
    // ------------------------------------------------------------------

    public function keyResults(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return $this->respondError('Goal not found.', 404);
        }
        return $this->respondSuccess(KeyResultResource::collection($goal->keyResults()->get()));
    }

    public function addKeyResult(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return $this->respondError('Goal not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'current_value' => ['nullable', 'numeric'],
            'target_value' => ['required', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'in:on_track,at_risk,behind,done'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $kr = $goal->keyResults()->create($validated);
        $kr->recompute();
        $this->rollupAncestors($goal);

        return $this->respondCreated(KeyResultResource::make($kr));
    }

    public function updateKeyResult(Request $request, Goal $goal, KeyResult $keyResult)
    {
        if ($goal->user_id !== $request->user()->id || $keyResult->goal_id !== $goal->id) {
            return $this->respondError('Key result not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'current_value' => ['nullable', 'numeric'],
            'target_value' => ['sometimes', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'in:on_track,at_risk,behind,done'],
        ]);

        $keyResult->update($validated);
        $keyResult->recompute();
        $this->rollupAncestors($goal);

        return $this->respondSuccess(KeyResultResource::make($keyResult));
    }

    public function deleteKeyResult(Request $request, Goal $goal, KeyResult $keyResult)
    {
        if ($goal->user_id !== $request->user()->id || $keyResult->goal_id !== $goal->id) {
            return $this->respondError('Key result not found.', 404);
        }
        $keyResult->delete();
        $this->rollupAncestors($goal);
        return $this->respondNoContent();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function rollupAncestors(Goal $goal): void
    {
        $current = $goal;
        $guard = 0;
        while ($current && $guard < 20) {
            $current->rollupProgress();
            $current = $current->parent()->first();
            $guard++;
        }
    }
}