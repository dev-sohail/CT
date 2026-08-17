<?php

namespace App\Domains\HomeTravelSuite\Http\Controllers;

use App\Domains\HomeTravelSuite\Http\Resources\HomeTravelResource;
use App\Domains\HomeTravelSuite\Models\HomeTravelRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class HomeTravelController extends ApiController
{
    private const TYPES = ['appliances', 'utilities', 'garden', 'inventory', 'trips', 'travel_journal', 'travel_documents', 'vehicles', 'routes'];
    public function index(Request $request, string $type) { $this->validType($type); $items = HomeTravelRecord::forUser($request->user()->id)->where('record_type', $type)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->orderBy('due_date')->paginate($request->integer('per_page', 25)); return $this->respondPaginated($items, HomeTravelResource::class); }
    public function store(Request $request, string $type) { $this->validType($type); $record = HomeTravelRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request))); return $this->respondCreated(HomeTravelResource::make($record)->toArray($request)); }
    public function show(Request $request, string $type, HomeTravelRecord $homeTravelRecord) { $this->authorizeRecord($request, $type, $homeTravelRecord); return $this->respondSuccess(HomeTravelResource::make($homeTravelRecord)->toArray($request)); }
    public function update(Request $request, string $type, HomeTravelRecord $homeTravelRecord) { $this->authorizeRecord($request, $type, $homeTravelRecord); $homeTravelRecord->update($this->validateRecord($request, true)); return $this->respondSuccess(HomeTravelResource::make($homeTravelRecord)->toArray($request)); }
    public function destroy(Request $request, string $type, HomeTravelRecord $homeTravelRecord) { $this->authorizeRecord($request, $type, $homeTravelRecord); $homeTravelRecord->delete(); return $this->respondNoContent(); }
    public function summary(Request $request, string $type) { $this->validType($type); $items = HomeTravelRecord::forUser($request->user()->id)->where('record_type', $type)->get(); return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'upcoming' => $items->filter(fn (HomeTravelRecord $r) => $r->due_date && $r->due_date->between(now(), now()->addDays(30)))->count(), 'by_status' => $items->groupBy('status')->map->count(), 'total_amount' => (float) $items->sum('amount')]); }
    private function validType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, HomeTravelRecord $record): void { $this->validType($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'location' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:24'], 'date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date'], 'amount' => ['nullable', 'numeric', 'min:0'], 'unit' => ['nullable', 'string', 'max:32'], 'details' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]); }
}
