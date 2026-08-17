<?php

namespace App\Domains\WorkoutAndTrainingPlanner\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\WorkoutAndTrainingPlanner\Http\Resources\WorkoutResource;
use App\Domains\WorkoutAndTrainingPlanner\Models\Workout;
use Illuminate\Http\Request;

class WorkoutController extends ApiController
{
    public function index(Request $request)
    {
        $items = Workout::forUser($request->user()->id)->when($request->input('from'), fn ($q, $date) => $q->whereDate('scheduled_on', '>=', $date))->when($request->input('to'), fn ($q, $date) => $q->whereDate('scheduled_on', '<=', $date))->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))->orderByDesc('scheduled_on')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, WorkoutResource::class);
    }

    public function store(Request $request)
    {
        $workout = Workout::create(array_merge(['user_id' => $request->user()->id], $this->validateWorkout($request)));
        return $this->respondCreated(WorkoutResource::make($workout)->toArray($request));
    }

    public function show(Request $request, Workout $workout)
    {
        $this->authorizeOwner($request, $workout);
        return $this->respondSuccess(WorkoutResource::make($workout)->toArray($request));
    }

    public function update(Request $request, Workout $workout)
    {
        $this->authorizeOwner($request, $workout);
        $workout->update($this->validateWorkout($request, true));
        return $this->respondSuccess(WorkoutResource::make($workout)->toArray($request));
    }

    public function destroy(Request $request, Workout $workout)
    {
        $this->authorizeOwner($request, $workout);
        $workout->delete();
        return $this->respondNoContent();
    }

    public function complete(Request $request, Workout $workout)
    {
        $this->authorizeOwner($request, $workout);
        $workout->update(['status' => 'completed', 'started_at' => $workout->started_at ?: now(), 'completed_at' => now()]);
        return $this->respondSuccess(WorkoutResource::make($workout)->toArray($request));
    }

    public function stats(Request $request)
    {
        $items = Workout::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'completed' => $items->where('status', 'completed')->count(), 'planned' => $items->where('status', 'planned')->count(), 'total_minutes' => (int) $items->sum('duration_minutes'), 'total_calories' => (int) $items->sum('calories_burned'), 'by_type' => $items->groupBy('type')->map->count()]);
    }

    private function validateWorkout(Request $request, bool $partial = false): array
    {
        return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'type' => ['sometimes', 'in:strength,cardio,mobility,sport,other'], 'scheduled_on' => ['nullable', 'date'], 'started_at' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date'], 'duration_minutes' => ['nullable', 'integer', 'min:0'], 'calories_burned' => ['nullable', 'integer', 'min:0'], 'status' => ['sometimes', 'in:planned,in_progress,completed,skipped'], 'exercises' => ['nullable', 'array'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, Workout $workout): void
    {
        if ($workout->user_id !== $request->user()->id) abort(404);
    }
}
