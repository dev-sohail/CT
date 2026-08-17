<?php

namespace App\Domains\LifeTimelineAndHistoryArchive\Http\Controllers;

use App\Domains\LifeTimelineAndHistoryArchive\Http\Resources\TimelineEventResource;
use App\Domains\LifeTimelineAndHistoryArchive\Models\TimelineEvent;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class TimelineEventController extends ApiController
{
    public function index(Request $request)
    {
        $items = TimelineEvent::forUser($request->user()->id)
            ->when($request->input('from'), fn ($q, $date) => $q->whereDate('event_date', '>=', $date))
            ->when($request->input('to'), fn ($q, $date) => $q->whereDate('event_date', '<=', $date))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('category'), fn ($q, $category) => $q->where('category', $category))
            ->when($request->input('tag'), fn ($q, $tag) => $q->whereJsonContains('tags', $tag))
            ->orderByDesc('event_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, TimelineEventResource::class);
    }

    public function store(Request $request)
    {
        $event = TimelineEvent::create(array_merge(['user_id' => $request->user()->id], $this->validateEvent($request)));
        return $this->respondCreated(TimelineEventResource::make($event)->toArray($request));
    }

    public function show(Request $request, TimelineEvent $timelineEvent)
    {
        $this->authorizeOwner($request, $timelineEvent);
        return $this->respondSuccess(TimelineEventResource::make($timelineEvent)->toArray($request));
    }

    public function update(Request $request, TimelineEvent $timelineEvent)
    {
        $this->authorizeOwner($request, $timelineEvent);
        $timelineEvent->update($this->validateEvent($request, true));
        return $this->respondSuccess(TimelineEventResource::make($timelineEvent)->toArray($request));
    }

    public function destroy(Request $request, TimelineEvent $timelineEvent)
    {
        $this->authorizeOwner($request, $timelineEvent);
        $timelineEvent->delete();
        return $this->respondNoContent();
    }

    public function milestones(Request $request)
    {
        $items = TimelineEvent::forUser($request->user()->id)->where('type', 'milestone')->orderByDesc('event_date')->get();
        return $this->respondSuccess(TimelineEventResource::collection($items));
    }

    public function stats(Request $request)
    {
        $items = TimelineEvent::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'milestones' => $items->where('type', 'milestone')->count(), 'by_type' => $items->groupBy('type')->map->count(), 'by_category' => $items->groupBy('category')->map->count(), 'average_significance' => $items->isEmpty() ? 0 : round($items->avg('significance'), 2), 'years' => $items->groupBy(fn (TimelineEvent $event) => $event->event_date->year)->map->count()]);
    }

    private function validateEvent(Request $request, bool $partial = false): array
    {
        return $request->validate(['event_date' => [$partial ? 'sometimes' : 'required', 'date'], 'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'type' => ['sometimes', 'in:event,milestone,turning_point,achievement'], 'category' => ['nullable', 'string', 'max:64'], 'significance' => ['sometimes', 'integer', 'between:1,5'], 'location' => ['nullable', 'string', 'max:255'], 'people' => ['nullable', 'array'], 'tags' => ['nullable', 'array'], 'tags.*' => ['string', 'max:64'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, TimelineEvent $event): void
    {
        if ($event->user_id !== $request->user()->id) abort(404);
    }
}
