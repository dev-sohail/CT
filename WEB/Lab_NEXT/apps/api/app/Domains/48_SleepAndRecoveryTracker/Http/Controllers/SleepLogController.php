<?php

namespace App\Domains\SleepAndRecoveryTracker\Http\Controllers;

use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use App\Domains\SleepAndRecoveryTracker\Http\Resources\SleepLogResource;
use App\Domains\SleepAndRecoveryTracker\Models\SleepLog;
use Illuminate\Http\Request;

class SleepLogController extends ApiController
{
    public function index(Request $request)
    {
        $items = SleepLog::forUser($request->user()->id)->when($request->input('from'), fn ($q, $date) => $q->whereDate('sleep_date', '>=', $date))->when($request->input('to'), fn ($q, $date) => $q->whereDate('sleep_date', '<=', $date))->orderByDesc('sleep_date')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, SleepLogResource::class);
    }

    public function store(Request $request)
    {
        $data = $this->validateSleep($request);
        $log = SleepLog::updateOrCreate(['user_id' => $request->user()->id, 'sleep_date' => $data['sleep_date']], $data);
        return $this->respondCreated(SleepLogResource::make($log)->toArray($request));
    }

    public function show(Request $request, SleepLog $sleepLog)
    {
        $this->authorizeOwner($request, $sleepLog);
        return $this->respondSuccess(SleepLogResource::make($sleepLog)->toArray($request));
    }

    public function update(Request $request, SleepLog $sleepLog)
    {
        $this->authorizeOwner($request, $sleepLog);
        $sleepLog->update($this->validateSleep($request, true));
        return $this->respondSuccess(SleepLogResource::make($sleepLog)->toArray($request));
    }

    public function destroy(Request $request, SleepLog $sleepLog)
    {
        $this->authorizeOwner($request, $sleepLog);
        $sleepLog->delete();
        return $this->respondNoContent();
    }

    public function today(Request $request)
    {
        $log = SleepLog::forUser($request->user()->id)->whereDate('sleep_date', $request->input('date', now()->toDateString()))->first();
        return $this->respondSuccess($log ? SleepLogResource::make($log) : null);
    }

    public function stats(Request $request)
    {
        $items = SleepLog::forUser($request->user()->id)->get();
        return $this->respondSuccess(['total_nights' => $items->count(), 'average_duration_minutes' => $items->whereNotNull('duration_minutes')->isEmpty() ? 0 : round($items->whereNotNull('duration_minutes')->avg('duration_minutes'), 2), 'average_quality' => $items->whereNotNull('quality')->isEmpty() ? 0 : round($items->whereNotNull('quality')->avg('quality'), 2), 'total_interruptions' => (int) $items->sum('interruptions'), 'best_quality' => $items->max('quality')]);
    }

    private function validateSleep(Request $request, bool $partial = false): array
    {
        return $request->validate(['sleep_date' => [$partial ? 'sometimes' : 'required', 'date'], 'bedtime' => ['nullable', 'date'], 'wake_time' => ['nullable', 'date'], 'duration_minutes' => ['nullable', 'integer', 'min:0'], 'quality' => ['nullable', 'integer', 'between:1,10'], 'interruptions' => ['sometimes', 'integer', 'min:0'], 'deep_minutes' => ['nullable', 'integer', 'min:0'], 'light_minutes' => ['nullable', 'integer', 'min:0'], 'rem_minutes' => ['nullable', 'integer', 'min:0'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
    }

    private function authorizeOwner(Request $request, SleepLog $log): void
    {
        if ($log->user_id !== $request->user()->id) abort(404);
    }
}
