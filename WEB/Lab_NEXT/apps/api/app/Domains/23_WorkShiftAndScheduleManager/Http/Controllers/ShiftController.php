<?php

namespace App\Domains\WorkShiftAndScheduleManager\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\WorkShiftAndScheduleManager\Http\Resources\ShiftResource;
use App\Domains\WorkShiftAndScheduleManager\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends ApiController
{
    public function index(Request $request)
    {
        $shifts = Shift::forUser($request->user()->id)
            ->when($request->input('from'), fn ($q, $f) => $q->where('starts_at', '>=', Carbon::parse($f)))
            ->when($request->input('to'), fn ($q, $t) => $q->where('starts_at', '<=', Carbon::parse($t)))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('starts_at')
            ->get();

        return $this->respondSuccess(ShiftResource::collection($shifts));
    }

    public function store(Request $request)
    {
        $validated = $this->validateShift($request);

        $shift = Shift::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));

        return $this->respondCreated(ShiftResource::make($shift));
    }

    public function show(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return $this->respondError('Shift not found.', 404);
        }
        return $this->respondSuccess(ShiftResource::make($shift));
    }

    public function update(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return $this->respondError('Shift not found.', 404);
        }
        $shift->update($this->validateShift($request, true));
        return $this->respondSuccess(ShiftResource::make($shift));
    }

    public function destroy(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return $this->respondError('Shift not found.', 404);
        }
        $shift->delete();
        return $this->respondNoContent();
    }

    public function conflicts(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return $this->respondError('Shift not found.', 404);
        }
        return $this->respondSuccess(ShiftResource::collection($shift->conflicts()->get()));
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $starts = Carbon::parse($validated['starts_at']);
        $ends = Carbon::parse($validated['ends_at']);
        $excludeId = $request->input('exclude_id');

        $query = Shift::forUser($request->user()->id)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($starts, $ends) {
                $q->whereBetween('starts_at', [$starts, $ends])
                    ->orWhereBetween('ends_at', [$starts, $ends])
                    ->orWhere(function ($inner) use ($starts, $ends) {
                        $inner->where('starts_at', '<=', $starts)
                            ->where('ends_at', '>=', $ends);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflicts = $query->get();

        return $this->respondSuccess([
            'has_conflict' => $conflicts->isNotEmpty(),
            'conflicts' => ShiftResource::collection($conflicts),
        ]);
    }

    public function monthly(Request $request)
    {
        $month = $request->input('month') ? Carbon::parse($request->input('month')) : now();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $shifts = Shift::forUser($request->user()->id)
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<=', $to)
            ->get();

        $earnings = $shifts->where('status', '!=', 'cancelled')
            ->map(fn (Shift $s) => $s->estimatedEarnings())
            ->filter()
            ->sum();
        $hours = $shifts->where('status', '!=', 'cancelled')->sum(fn (Shift $s) => $s->durationHours());

        return $this->respondSuccess([
            'month' => $from->format('Y-m'),
            'shift_count' => $shifts->count(),
            'total_hours' => round($hours, 2),
            'estimated_earnings' => round($earnings, 2),
            'shifts' => ShiftResource::collection($shifts),
        ]);
    }

    private function validateShift(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'starts_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'ends_at' => [$partial ? 'sometimes' : 'required', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:scheduled,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}