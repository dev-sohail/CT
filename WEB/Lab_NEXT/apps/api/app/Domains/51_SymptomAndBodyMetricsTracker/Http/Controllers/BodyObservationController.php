<?php

namespace App\Domains\SymptomAndBodyMetricsTracker\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\SymptomAndBodyMetricsTracker\Http\Resources\BodyObservationResource;
use App\Domains\SymptomAndBodyMetricsTracker\Models\BodyObservation;
use Illuminate\Http\Request;

class BodyObservationController extends ApiController
{
    public function index(Request $request)
    {
        $items = BodyObservation::forUser($request->user()->id)->when($request->input('observation_type'), fn ($q, $type) => $q->where('observation_type', $type))->when($request->input('name'), fn ($q, $name) => $q->where('name', $name))->when($request->input('from'), fn ($q, $date) => $q->whereDate('observed_at', '>=', $date))->when($request->input('to'), fn ($q, $date) => $q->whereDate('observed_at', '<=', $date))->orderByDesc('observed_at')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, BodyObservationResource::class);
    }

    public function store(Request $request)
    {
        $observation = BodyObservation::create(array_merge(['user_id' => $request->user()->id], $this->validateObservation($request)));
        return $this->respondCreated(BodyObservationResource::make($observation)->toArray($request));
    }

    public function show(Request $request, BodyObservation $bodyObservation)
    {
        $this->authorizeOwner($request, $bodyObservation);
        return $this->respondSuccess(BodyObservationResource::make($bodyObservation)->toArray($request));
    }

    public function update(Request $request, BodyObservation $bodyObservation)
    {
        $this->authorizeOwner($request, $bodyObservation);
        $bodyObservation->update($this->validateObservation($request, true));
        return $this->respondSuccess(BodyObservationResource::make($bodyObservation)->toArray($request));
    }

    public function destroy(Request $request, BodyObservation $bodyObservation)
    {
        $this->authorizeOwner($request, $bodyObservation);
        $bodyObservation->delete();
        return $this->respondNoContent();
    }

    public function trend(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:128']]);
        $items = BodyObservation::forUser($request->user()->id)->where('name', $request->input('name'))->whereNotNull('value')->when($request->input('from'), fn ($q, $date) => $q->whereDate('observed_at', '>=', $date))->orderBy('observed_at')->get();
        return $this->respondSuccess(BodyObservationResource::collection($items));
    }

    public function stats(Request $request)
    {
        $items = BodyObservation::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'symptoms' => $items->where('observation_type', 'symptom')->count(), 'metrics' => $items->where('observation_type', 'metric')->count(), 'by_name' => $items->groupBy('name')->map->count(), 'average_symptom_severity' => $items->where('observation_type', 'symptom')->whereNotNull('severity')->isEmpty() ? 0 : round($items->where('observation_type', 'symptom')->whereNotNull('severity')->avg('severity'), 2)]);
    }

    private function validateObservation(Request $request, bool $partial = false): array
    {
        return $request->validate(['observed_at' => [$partial ? 'sometimes' : 'required', 'date'], 'observation_type' => ['sometimes', 'in:symptom,metric'], 'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:128'], 'value' => ['nullable', 'numeric'], 'unit' => ['nullable', 'string', 'max:32'], 'severity' => ['nullable', 'integer', 'between:1,10'], 'description' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, BodyObservation $observation): void
    {
        if ($observation->user_id !== $request->user()->id) abort(404);
    }
}
