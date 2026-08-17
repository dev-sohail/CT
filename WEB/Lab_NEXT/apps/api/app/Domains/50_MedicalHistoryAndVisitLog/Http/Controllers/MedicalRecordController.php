<?php

namespace App\Domains\MedicalHistoryAndVisitLog\Http\Controllers;

use App\Domains\MedicalHistoryAndVisitLog\Http\Resources\MedicalRecordResource;
use App\Domains\MedicalHistoryAndVisitLog\Models\MedicalRecord;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class MedicalRecordController extends ApiController
{
    public function index(Request $request)
    {
        $items = MedicalRecord::forUser($request->user()->id)->when($request->input('record_type'), fn ($q, $type) => $q->where('record_type', $type))->when($request->input('from'), fn ($q, $date) => $q->whereDate('record_date', '>=', $date))->when($request->input('to'), fn ($q, $date) => $q->whereDate('record_date', '<=', $date))->orderByDesc('record_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, MedicalRecordResource::class);
    }

    public function store(Request $request)
    {
        $record = MedicalRecord::create(array_merge(['user_id' => $request->user()->id], $this->validateRecord($request)));
        return $this->respondCreated(MedicalRecordResource::make($record)->toArray($request));
    }

    public function show(Request $request, MedicalRecord $medicalRecord)
    {
        $this->authorizeOwner($request, $medicalRecord);
        return $this->respondSuccess(MedicalRecordResource::make($medicalRecord)->toArray($request));
    }

    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $this->authorizeOwner($request, $medicalRecord);
        $medicalRecord->update($this->validateRecord($request, true));
        return $this->respondSuccess(MedicalRecordResource::make($medicalRecord)->toArray($request));
    }

    public function destroy(Request $request, MedicalRecord $medicalRecord)
    {
        $this->authorizeOwner($request, $medicalRecord);
        $medicalRecord->delete();
        return $this->respondNoContent();
    }

    public function followUps(Request $request)
    {
        $items = MedicalRecord::forUser($request->user()->id)->whereNotNull('follow_up_date')->whereDate('follow_up_date', '>=', now())->orderBy('follow_up_date')->get();
        return $this->respondSuccess(MedicalRecordResource::collection($items));
    }

    public function stats(Request $request)
    {
        $items = MedicalRecord::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total' => $items->count(), 'visits' => $items->where('record_type', 'visit')->count(), 'conditions' => $items->where('record_type', 'condition')->count(), 'by_provider' => $items->groupBy('provider')->map->count(), 'upcoming_follow_ups' => $items->filter(fn (MedicalRecord $record) => $record->follow_up_date && $record->follow_up_date->gte(now()->startOfDay()))->count()]);
    }

    private function validateRecord(Request $request, bool $partial = false): array
    {
        return $request->validate(['record_type' => ['sometimes', 'in:visit,condition,procedure,test'], 'record_date' => [$partial ? 'sometimes' : 'required', 'date'], 'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'provider' => ['nullable', 'string', 'max:255'], 'facility' => ['nullable', 'string', 'max:255'], 'diagnosis' => ['nullable', 'string'], 'symptoms' => ['nullable', 'string'], 'treatment' => ['nullable', 'string'], 'medications' => ['nullable', 'array'], 'follow_up_date' => ['nullable', 'date'], 'status' => ['sometimes', 'in:active,resolved,ongoing'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, MedicalRecord $record): void
    {
        if ($record->user_id !== $request->user()->id) abort(404);
    }
}
