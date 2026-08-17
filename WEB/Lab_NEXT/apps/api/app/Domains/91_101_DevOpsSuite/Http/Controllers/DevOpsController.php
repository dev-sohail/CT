<?php

namespace App\Domains\DevOpsSuite\Http\Controllers;

use App\Domains\DevOpsSuite\Http\Resources\DevOpsResource;
use App\Domains\DevOpsSuite\Models\DevOpsRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class DevOpsController extends ApiController
{
    private const TYPES = ['api_testing', 'scaffolding', 'schemas', 'packages', 'environments', 'deployments', 'containers', 'servers', 'monitoring', 'certificates', 'docs', 'backup'];
    public function index(Request $request, string $type) { $this->valid($type); $items = DevOpsRecord::forUser($request->user()->id)->where('record_type', $type)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->orderBy('name')->paginate($request->integer('per_page', 25)); return $this->respondPaginated($items, DevOpsResource::class); }
    public function store(Request $request, string $type) { $this->valid($type); $record = DevOpsRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request))); return $this->respondCreated(DevOpsResource::make($record)->toArray($request)); }
    public function show(Request $request, string $type, DevOpsRecord $devOpsRecord) { $this->authorizeRecord($request, $type, $devOpsRecord); return $this->respondSuccess(DevOpsResource::make($devOpsRecord)->toArray($request)); }
    public function update(Request $request, string $type, DevOpsRecord $devOpsRecord) { $this->authorizeRecord($request, $type, $devOpsRecord); $devOpsRecord->update($this->validateRecord($request, true)); return $this->respondSuccess(DevOpsResource::make($devOpsRecord)->toArray($request)); }
    public function destroy(Request $request, string $type, DevOpsRecord $devOpsRecord) { $this->authorizeRecord($request, $type, $devOpsRecord); $devOpsRecord->delete(); return $this->respondNoContent(); }
    public function stats(Request $request, string $type) { $this->valid($type); $items = DevOpsRecord::forUser($request->user()->id)->where('record_type', $type)->get(); return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'by_status' => $items->groupBy('status')->map->count(), 'by_environment' => $items->groupBy('environment')->map->count()]); }
    private function valid(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, DevOpsRecord $record): void { $this->valid($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:24'], 'environment' => ['nullable', 'string', 'max:64'], 'url' => ['nullable', 'url', 'max:1024'], 'description' => ['nullable', 'string'], 'config' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]); }
}
