<?php

namespace App\Domains\SecurityAnalyticsSuite\Http\Controllers;

use App\Domains\SecurityAnalyticsSuite\Http\Resources\SecurityAnalyticsResource;
use App\Domains\SecurityAnalyticsSuite\Models\SecurityAnalyticsRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class SecurityAnalyticsController extends ApiController
{
    private const TYPES = ['security_audits', 'devices', 'analytics', 'life_statistics', 'reports'];
    public function index(Request $request, string $type) { $this->valid($type); $items = SecurityAnalyticsRecord::forUser($request->user()->id)->where('record_type', $type)->orderByDesc('recorded_at')->paginate($request->integer('per_page', 25)); return $this->respondPaginated($items, SecurityAnalyticsResource::class); }
    public function store(Request $request, string $type) { $this->valid($type); $record = SecurityAnalyticsRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request))); return $this->respondCreated(SecurityAnalyticsResource::make($record)->toArray($request)); }
    public function show(Request $request, string $type, SecurityAnalyticsRecord $securityAnalyticsRecord) { $this->authorizeRecord($request, $type, $securityAnalyticsRecord); return $this->respondSuccess(SecurityAnalyticsResource::make($securityAnalyticsRecord)->toArray($request)); }
    public function update(Request $request, string $type, SecurityAnalyticsRecord $securityAnalyticsRecord) { $this->authorizeRecord($request, $type, $securityAnalyticsRecord); $securityAnalyticsRecord->update($this->validateRecord($request, true)); return $this->respondSuccess(SecurityAnalyticsResource::make($securityAnalyticsRecord)->toArray($request)); }
    public function destroy(Request $request, string $type, SecurityAnalyticsRecord $securityAnalyticsRecord) { $this->authorizeRecord($request, $type, $securityAnalyticsRecord); $securityAnalyticsRecord->delete(); return $this->respondNoContent(); }
    public function summary(Request $request, string $type) { $this->valid($type); $items = SecurityAnalyticsRecord::forUser($request->user()->id)->where('record_type', $type)->get(); return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'total_value' => (float) $items->sum('value'), 'by_status' => $items->groupBy('status')->map->count()]); }
    private function valid(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, SecurityAnalyticsRecord $record): void { $this->valid($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:24'], 'value' => ['nullable', 'numeric'], 'recorded_at' => ['nullable', 'date'], 'description' => ['nullable', 'string'], 'details' => ['nullable', 'array']]); }
}
