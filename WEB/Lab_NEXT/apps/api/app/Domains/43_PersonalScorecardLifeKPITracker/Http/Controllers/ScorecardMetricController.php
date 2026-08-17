<?php

namespace App\Domains\PersonalScorecardLifeKPITracker\Http\Controllers;

use App\Domains\PersonalScorecardLifeKPITracker\Models\ScorecardMetric;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ScorecardMetricController extends ApiController
{
    public function index(Request $request)
    {
        $items = ScorecardMetric::forUser($request->user()->id)->with(['measurements' => fn ($q) => $q->latest('measured_on')->limit(1)])->orderBy('name')->get();
        return $this->respondSuccess($items->map(fn (ScorecardMetric $m) => $this->present($m)));
    }

    public function store(Request $request)
    {
        $metric = ScorecardMetric::create(array_merge(['user_id' => $request->user()->id], $this->validateMetric($request)));
        return $this->respondCreated($this->present($metric));
    }

    public function show(Request $request, ScorecardMetric $metric)
    {
        $this->authorizeOwner($request, $metric);
        return $this->respondSuccess($this->present($metric->load('measurements')));
    }

    public function update(Request $request, ScorecardMetric $metric)
    {
        $this->authorizeOwner($request, $metric);
        $metric->update($this->validateMetric($request, true));
        return $this->respondSuccess($this->present($metric));
    }

    public function destroy(Request $request, ScorecardMetric $metric)
    {
        $this->authorizeOwner($request, $metric);
        $metric->delete();
        return $this->respondNoContent();
    }

    public function measure(Request $request, ScorecardMetric $metric)
    {
        $this->authorizeOwner($request, $metric);
        $data = $request->validate(['measured_on' => ['sometimes', 'date'], 'value' => ['required', 'numeric'], 'note' => ['nullable', 'string']]);
        $data['measured_on'] ??= now()->toDateString();
        $measurement = $metric->measurements()->updateOrCreate(['measured_on' => $data['measured_on']], ['user_id' => $request->user()->id, 'value' => $data['value'], 'note' => $data['note'] ?? null]);
        return $this->respondSuccess(['measurement' => $measurement, 'score' => $this->score($metric, (float) $measurement->value)]);
    }

    public function summary(Request $request)
    {
        $items = ScorecardMetric::forUser($request->user()->id)->with('measurements')->get();
        return $this->respondSuccess(['total' => $items->count(), 'active' => $items->where('active', true)->count(), 'metrics' => $items->map(fn (ScorecardMetric $m) => $this->present($m))]);
    }

    private function present(ScorecardMetric $metric): array
    {
        $latest = $metric->measurements->sortByDesc('measured_on')->first();
        return ['id' => $metric->id, 'name' => $metric->name, 'category' => $metric->category, 'unit' => $metric->unit, 'target' => $metric->target !== null ? (float) $metric->target : null, 'direction' => $metric->direction, 'frequency' => $metric->frequency, 'active' => (bool) $metric->active, 'latest' => $latest ? ['date' => $latest->measured_on->toDateString(), 'value' => (float) $latest->value, 'score' => $this->score($metric, (float) $latest->value)] : null];
    }

    private function score(ScorecardMetric $metric, float $value): ?float
    {
        if ($metric->target === null || (float) $metric->target == 0.0) return null;
        $ratio = $metric->direction === 'lower' ? (float) $metric->target / $value : $value / (float) $metric->target;
        return round(min(1, $ratio) * 100, 1);
    }

    private function validateMetric(Request $request, bool $partial = false): array
    {
        return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:64'], 'unit' => ['nullable', 'string', 'max:32'], 'target' => ['nullable', 'numeric'], 'direction' => ['sometimes', 'in:higher,lower'], 'frequency' => ['sometimes', 'in:daily,weekly,monthly'], 'active' => ['boolean'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, ScorecardMetric $metric): void
    {
        if ($metric->user_id !== $request->user()->id) abort(404);
    }
}
