<?php

namespace App\Domains\MedicationAndPrescriptionTracker\Http\Controllers;

use App\Domains\MedicationAndPrescriptionTracker\Http\Resources\MedicationResource;
use App\Domains\MedicationAndPrescriptionTracker\Models\Medication;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class MedicationController extends ApiController
{
    public function index(Request $request)
    {
        $items = Medication::forUser($request->user()->id)->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))->orderBy('name')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, MedicationResource::class);
    }

    public function store(Request $request)
    {
        $medication = Medication::create(array_merge(['user_id' => $request->user()->id], $this->validateMedication($request)));
        return $this->respondCreated(MedicationResource::make($medication)->toArray($request));
    }

    public function show(Request $request, Medication $medication)
    {
        $this->authorizeOwner($request, $medication);
        return $this->respondSuccess(MedicationResource::make($medication)->toArray($request));
    }

    public function update(Request $request, Medication $medication)
    {
        $this->authorizeOwner($request, $medication);
        $medication->update($this->validateMedication($request, true));
        return $this->respondSuccess(MedicationResource::make($medication)->toArray($request));
    }

    public function destroy(Request $request, Medication $medication)
    {
        $this->authorizeOwner($request, $medication);
        $medication->delete();
        return $this->respondNoContent();
    }

    public function log(Request $request, Medication $medication)
    {
        $this->authorizeOwner($request, $medication);
        $data = $request->validate(['taken_at' => ['sometimes', 'date'], 'status' => ['sometimes', 'in:taken,skipped,missed'], 'note' => ['nullable', 'string']]);
        $data['taken_at'] ??= now()->toDateTimeString();
        $log = $medication->logs()->create(array_merge(['user_id' => $request->user()->id], $data));
        return $this->respondSuccess($log);
    }

    public function adherence(Request $request, Medication $medication)
    {
        $this->authorizeOwner($request, $medication);
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $logs = $medication->logs()->whereDate('taken_at', '>=', $from)->get();
        $taken = $logs->where('status', 'taken')->count();
        return $this->respondSuccess(['from' => $from, 'to' => now()->toDateString(), 'total_logs' => $logs->count(), 'taken' => $taken, 'missed' => $logs->where('status', 'missed')->count(), 'skipped' => $logs->where('status', 'skipped')->count(), 'adherence_rate' => $logs->isEmpty() ? 0 : round($taken / $logs->count() * 100, 1)]);
    }

    public function stats(Request $request)
    {
        $items = Medication::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'active' => $items->where('status', 'active')->count(), 'completed' => $items->where('status', 'completed')->count(), 'by_frequency' => $items->groupBy('frequency')->map->count()]);
    }

    private function validateMedication(Request $request, bool $partial = false): array
    {
        return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'generic_name' => ['nullable', 'string', 'max:255'], 'dosage' => ['nullable', 'string', 'max:64'], 'form' => ['nullable', 'string', 'max:32'], 'frequency' => ['sometimes', 'string', 'max:32'], 'schedule' => ['nullable', 'array'], 'started_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:started_on'], 'prescriber' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'in:active,paused,completed'], 'instructions' => ['nullable', 'string'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, Medication $medication): void
    {
        if ($medication->user_id !== $request->user()->id) abort(404);
    }
}
