<?php

namespace App\Domains\CareerBusinessSuite\Http\Controllers;

use App\Domains\CareerBusinessSuite\Http\Resources\CareerBusinessResource;
use App\Domains\CareerBusinessSuite\Models\CareerBusinessRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class CareerBusinessController extends ApiController
{
    private const TYPES = ['resumes', 'job_applications', 'achievements', 'meetings', 'invoices', 'clients', 'relationships', 'follow_ups', 'templates', 'important_dates', 'gifts', 'family_records'];
    public function index(Request $request, string $type) { $this->validType($type); $items = CareerBusinessRecord::forUser($request->user()->id)->where('record_type', $type)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->orderByDesc('record_date')->paginate($request->integer('per_page', 25)); return $this->respondPaginated($items, CareerBusinessResource::class); }
    public function store(Request $request, string $type) { $this->validType($type); $record = CareerBusinessRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request))); return $this->respondCreated(CareerBusinessResource::make($record)->toArray($request)); }
    public function show(Request $request, string $type, CareerBusinessRecord $careerBusinessRecord) { $this->authorizeRecord($request, $type, $careerBusinessRecord); return $this->respondSuccess(CareerBusinessResource::make($careerBusinessRecord)->toArray($request)); }
    public function update(Request $request, string $type, CareerBusinessRecord $careerBusinessRecord) { $this->authorizeRecord($request, $type, $careerBusinessRecord); $careerBusinessRecord->update($this->validateRecord($request, true)); return $this->respondSuccess(CareerBusinessResource::make($careerBusinessRecord)->toArray($request)); }
    public function destroy(Request $request, string $type, CareerBusinessRecord $careerBusinessRecord) { $this->authorizeRecord($request, $type, $careerBusinessRecord); $careerBusinessRecord->delete(); return $this->respondNoContent(); }
    public function summary(Request $request, string $type) { $this->validType($type); $items = CareerBusinessRecord::forUser($request->user()->id)->where('record_type', $type)->get(); return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'upcoming' => $items->filter(fn (CareerBusinessRecord $r) => $r->next_date && $r->next_date->between(now(), now()->addDays(30)))->count(), 'total_amount' => (float) $items->sum('amount'), 'by_status' => $items->groupBy('status')->map->count()]); }
    private function validType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, CareerBusinessRecord $record): void { $this->validType($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'organization' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:24'], 'record_date' => ['nullable', 'date'], 'next_date' => ['nullable', 'date'], 'amount' => ['nullable', 'numeric', 'min:0'], 'description' => ['nullable', 'string'], 'details' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]); }
}
