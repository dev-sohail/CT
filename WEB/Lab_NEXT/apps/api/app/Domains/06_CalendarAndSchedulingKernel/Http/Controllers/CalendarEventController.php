<?php

namespace App\Domains\CalendarAndSchedulingKernel\Http\Controllers;

use App\Domains\CalendarAndSchedulingKernel\Http\Resources\CalendarEventResource;
use App\Domains\CalendarAndSchedulingKernel\Models\CalendarEvent;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class CalendarEventController extends ApiController
{
    public function index(Request $request)
    {
        $query = CalendarEvent::forUser($request->user()->id)
            ->active()
            ->between($request->input('from'), $request->input('to'))
            ->orderBy('starts_at');

        $events = $query->get();

        return $this->respondSuccess(CalendarEventResource::collection($events));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_all_day' => ['boolean'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:tentative,confirmed,cancelled'],
            'recurrence_rule' => ['nullable', 'string', 'max:255'],
            'recurrence_ends_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        $event = CalendarEvent::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->respondCreated(CalendarEventResource::make($event->fresh()));
    }

    public function show(Request $request, CalendarEvent $event)
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->respondError('Calendar event not found.', 404);
        }

        return $this->respondSuccess(CalendarEventResource::make($event));
    }

    public function update(Request $request, CalendarEvent $event)
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->respondError('Calendar event not found.', 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_all_day' => ['boolean'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:tentative,confirmed,cancelled'],
            'recurrence_rule' => ['nullable', 'string', 'max:255'],
            'recurrence_ends_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        $event->update($validated);

        return $this->respondSuccess(CalendarEventResource::make($event->fresh()));
    }

    public function destroy(Request $request, CalendarEvent $event)
    {
        if ($event->user_id !== $request->user()->id) {
            return $this->respondError('Calendar event not found.', 404);
        }

        $event->delete();

        return $this->respondNoContent();
    }

    /**
     * Expand recurring events into virtual occurrences within a date range.
     */
    public function expand(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $events = CalendarEvent::forUser($request->user()->id)
            ->active()
            ->whereNotNull('recurrence_rule')
            ->get();

        $occurrences = [];

        foreach ($events as $event) {
            $expanded = $event->expandOccurrences($validated['from'], $validated['to']);
            foreach ($expanded as $occ) {
                $occurrences[] = $occ;
            }
        }

        // Also include non-recurring events within the range
        $oneTime = CalendarEvent::forUser($request->user()->id)
            ->active()
            ->whereNull('recurrence_rule')
            ->between($validated['from'], $validated['to'])
            ->get();

        foreach ($oneTime as $event) {
            $occurrences[] = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'is_all_day' => $event->is_all_day,
                'timezone' => $event->timezone,
                'status' => $event->status,
                'recurrence_rule' => null,
            ];
        }

        // Sort by starts_at
        usort($occurrences, fn($a, $b) => strcmp($a['starts_at'] ?? '', $b['starts_at'] ?? ''));

        return $this->respondSuccess($occurrences);
    }
}