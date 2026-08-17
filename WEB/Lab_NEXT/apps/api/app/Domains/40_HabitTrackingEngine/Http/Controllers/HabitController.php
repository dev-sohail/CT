<?php

namespace App\Domains\HabitTrackingEngine\Http\Controllers;

use App\Domains\HabitTrackingEngine\Http\Resources\HabitResource;
use App\Domains\HabitTrackingEngine\Models\Habit;
use App\Domains\HabitTrackingEngine\Services\HabitService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class HabitController extends ApiController
{
    public function __construct(private readonly HabitService $habits) {}

    public function index(Request $request)
    {
        $items = Habit::forUser($request->user()->id)
            ->when($request->has('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('name')->get();

        return $this->respondSuccess(HabitResource::collection($items));
    }

    public function store(Request $request)
    {
        $habit = Habit::create(array_merge(['user_id' => $request->user()->id], $this->validateHabit($request)));
        return $this->respondCreated(HabitResource::make($habit)->toArray($request));
    }

    public function show(Request $request, Habit $habit)
    {
        $this->authorizeOwner($request, $habit);
        return $this->respondSuccess(HabitResource::make($habit)->toArray($request));
    }

    public function update(Request $request, Habit $habit)
    {
        $this->authorizeOwner($request, $habit);
        $habit->update($this->validateHabit($request, true));
        return $this->respondSuccess(HabitResource::make($habit)->toArray($request));
    }

    public function destroy(Request $request, Habit $habit)
    {
        $this->authorizeOwner($request, $habit);
        $habit->delete();
        return $this->respondNoContent();
    }

    public function log(Request $request, Habit $habit)
    {
        $this->authorizeOwner($request, $habit);
        $data = $request->validate([
            'logged_for' => ['sometimes', 'date'],
            'count' => ['sometimes', 'integer', 'min:0'],
            'completed' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['logged_for'] ??= now()->toDateString();
        $log = $this->habits->log($habit, $request->user()->id, $data);
        return $this->respondSuccess(['log' => $log, 'streak' => $this->habits->streak($habit)]);
    }

    public function today(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $habits = Habit::forUser($request->user()->id)->where('active', true)->get();
        $items = $this->habits->today($habits, $date)->map(fn ($item) => [
            'habit' => HabitResource::make($item['habit']),
            'log' => $item['log'],
            'completed' => $item['completed'],
        ]);
        return $this->respondSuccess(['date' => $date, 'items' => $items->values()]);
    }

    public function stats(Request $request)
    {
        $habits = Habit::forUser($request->user()->id)->get();
        return $this->respondSuccess([
            'total' => $habits->count(),
            'active' => $habits->where('active', true)->count(),
            'completed_today' => $habits->filter(function (Habit $habit): bool {
                $log = $habit->logs()->whereDate('logged_for', now()->toDateString())->first();
                return $log !== null && $log->completed && $log->count >= $habit->target_count;
            })->count(),
            'streaks' => $habits->mapWithKeys(fn (Habit $habit) => [$habit->id => $this->habits->streak($habit)]),
        ]);
    }

    private function validateHabit(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['sometimes', 'in:daily,weekly,custom'],
            'target_count' => ['sometimes', 'integer', 'min:1'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'color' => ['nullable', 'string', 'max:32'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function authorizeOwner(Request $request, Habit $habit): void
    {
        if ($habit->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
