<?php

namespace App\Domains\EntertainmentWritingSuite\Http\Controllers;

use App\Domains\EntertainmentWritingSuite\Http\Resources\EntertainmentWritingResource;
use App\Domains\EntertainmentWritingSuite\Models\EntertainmentWritingRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class EntertainmentWritingController extends ApiController
{
    private const TYPES = ['watch', 'reading', 'music', 'chess', 'wishlist', 'writing', 'publishing', 'ideas'];
    public function index(Request $request, string $type) { $this->validType($type); $items = EntertainmentWritingRecord::forUser($request->user()->id)->where('record_type', $type)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->orderByDesc('record_date')->paginate($request->integer('per_page', 25)); return $this->respondPaginated($items, EntertainmentWritingResource::class); }
    public function store(Request $request, string $type) { $this->validType($type); $record = EntertainmentWritingRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request))); return $this->respondCreated(EntertainmentWritingResource::make($record)->toArray($request)); }
    public function show(Request $request, string $type, EntertainmentWritingRecord $entertainmentWritingRecord) { $this->authorizeRecord($request, $type, $entertainmentWritingRecord); return $this->respondSuccess(EntertainmentWritingResource::make($entertainmentWritingRecord)->toArray($request)); }
    public function update(Request $request, string $type, EntertainmentWritingRecord $entertainmentWritingRecord) { $this->authorizeRecord($request, $type, $entertainmentWritingRecord); $entertainmentWritingRecord->update($this->validateRecord($request, true)); return $this->respondSuccess(EntertainmentWritingResource::make($entertainmentWritingRecord)->toArray($request)); }
    public function destroy(Request $request, string $type, EntertainmentWritingRecord $entertainmentWritingRecord) { $this->authorizeRecord($request, $type, $entertainmentWritingRecord); $entertainmentWritingRecord->delete(); return $this->respondNoContent(); }
    public function stats(Request $request, string $type) { $this->validType($type); $items = EntertainmentWritingRecord::forUser($request->user()->id)->where('record_type', $type)->get(); return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'completed' => $items->where('status', 'completed')->count(), 'average_rating' => $items->whereNotNull('rating')->isEmpty() ? null : round($items->whereNotNull('rating')->avg('rating'), 2)]); }
    private function validType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, EntertainmentWritingRecord $record): void { $this->validType($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'creator' => ['nullable', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'max:24'], 'rating' => ['nullable', 'integer', 'between:1,10'], 'record_date' => ['nullable', 'date'], 'description' => ['nullable', 'string'], 'tags' => ['nullable', 'array'], 'details' => ['nullable', 'array']]); }
}
