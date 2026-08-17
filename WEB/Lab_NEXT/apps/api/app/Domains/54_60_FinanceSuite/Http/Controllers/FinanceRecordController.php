<?php

namespace App\Domains\FinanceSuite\Http\Controllers;

use App\Domains\FinanceSuite\Http\Resources\FinanceRecordResource;
use App\Domains\FinanceSuite\Models\FinanceRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class FinanceRecordController extends ApiController
{
    private const TYPES = ['bills', 'savings', 'investments', 'loans', 'insurance', 'tax_documents', 'purchases'];

    public function index(Request $request, string $type)
    {
        $this->validateType($type);
        $items = FinanceRecord::forUser($request->user()->id)->where('record_type', $type)->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))->orderBy('due_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, FinanceRecordResource::class);
    }

    public function store(Request $request, string $type)
    {
        $this->validateType($type);
        $record = FinanceRecord::create(array_merge(['user_id' => $request->user()->id, 'record_type' => $type], $this->validateRecord($request)));
        return $this->respondCreated(FinanceRecordResource::make($record)->toArray($request));
    }

    public function show(Request $request, string $type, FinanceRecord $financeRecord)
    {
        $this->authorizeRecord($request, $type, $financeRecord);
        return $this->respondSuccess(FinanceRecordResource::make($financeRecord)->toArray($request));
    }

    public function update(Request $request, string $type, FinanceRecord $financeRecord)
    {
        $this->authorizeRecord($request, $type, $financeRecord);
        $financeRecord->update($this->validateRecord($request, true));
        return $this->respondSuccess(FinanceRecordResource::make($financeRecord)->toArray($request));
    }

    public function destroy(Request $request, string $type, FinanceRecord $financeRecord)
    {
        $this->authorizeRecord($request, $type, $financeRecord);
        $financeRecord->delete();
        return $this->respondNoContent();
    }

    public function summary(Request $request, string $type)
    {
        $this->validateType($type);
        $items = FinanceRecord::forUser($request->user()->id)->where('record_type', $type)->get();
        return $this->respondSuccess(['type' => $type, 'total' => $items->count(), 'total_amount' => (float) $items->sum('amount'), 'target_amount' => (float) $items->sum('target_amount'), 'current_amount' => (float) $items->sum('current_amount'), 'upcoming' => $items->filter(fn (FinanceRecord $r) => $r->due_date && $r->due_date->between(now(), now()->addDays(30)))->count(), 'by_status' => $items->groupBy('status')->map->count()]);
    }

    private function validateType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function authorizeRecord(Request $request, string $type, FinanceRecord $record): void { $this->validateType($type); if ($record->user_id !== $request->user()->id || $record->record_type !== $type) abort(404); }
    private function validateRecord(Request $request, bool $partial = false): array { return $request->validate(['name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'amount' => ['nullable', 'numeric', 'min:0'], 'target_amount' => ['nullable', 'numeric', 'min:0'], 'current_amount' => ['nullable', 'numeric', 'min:0'], 'currency' => ['sometimes', 'size:3'], 'status' => ['sometimes', 'string', 'max:24'], 'due_date' => ['nullable', 'date'], 'institution' => ['nullable', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:64'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]); }
}
