<?php

namespace App\Domains\TimeAuditAndTimeBlockAnalyzer\Http\Controllers;

use App\Domains\TimeAuditAndTimeBlockAnalyzer\Http\Resources\TimeEntryResource;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Models\TimeEntry;
use App\Domains\TimeAuditAndTimeBlockAnalyzer\Services\TimeAuditService;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeEntryController extends ApiController
{
    public function __construct(private TimeAuditService $audit)
    {
    }

    public function index(Request $request)
    {
        $entries = TimeEntry::forUser($request->user()->id)
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->input('from'), fn ($q, $f) => $q->where('started_at', '>=', Carbon::parse($f)))
            ->when($request->input('to'), fn ($q, $t) => $q->where('started_at', '<=', Carbon::parse($t)))
            ->orderByDesc('started_at')
            ->limit((int) $request->input('limit', 100))
            ->get();

        return $this->respondSuccess(TimeEntryResource::collection($entries));
    }

    public function store(Request $request)
    {
        $validated = $this->validateEntry($request);

        $entry = TimeEntry::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));

        return $this->respondCreated(TimeEntryResource::make($entry));
    }

    public function show(Request $request, TimeEntry $entry)
    {
        if ($entry->user_id !== $request->user()->id) {
            return $this->respondError('Time entry not found.', 404);
        }
        return $this->respondSuccess(TimeEntryResource::make($entry));
    }

    public function update(Request $request, TimeEntry $entry)
    {
        if ($entry->user_id !== $request->user()->id) {
            return $this->respondError('Time entry not found.', 404);
        }
        $entry->update($this->validateEntry($request, true));
        return $this->respondSuccess(TimeEntryResource::make($entry));
    }

    public function destroy(Request $request, TimeEntry $entry)
    {
        if ($entry->user_id !== $request->user()->id) {
            return $this->respondError('Time entry not found.', 404);
        }
        $entry->delete();
        return $this->respondNoContent();
    }

    public function analytics(Request $request)
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->startOfWeek();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : now()->endOfWeek();

        return $this->respondSuccess($this->audit->analytics($request->user(), $from, $to));
    }

    private function validateEntry(Request $request, bool $partial = false): array
    {
        $rules = [
            'started_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'ended_at' => ['nullable', 'date', 'after:started_at'],
            'activity' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}