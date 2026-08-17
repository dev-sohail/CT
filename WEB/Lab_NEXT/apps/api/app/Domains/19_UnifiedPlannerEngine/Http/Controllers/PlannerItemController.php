<?php

namespace App\Domains\UnifiedPlannerEngine\Http\Controllers;

use App\Domains\UnifiedPlannerEngine\Http\Resources\PlannerItemResource;
use App\Domains\UnifiedPlannerEngine\Models\PlannerItem;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlannerItemController extends ApiController
{
    public function index(Request $request)
    {
        $items = PlannerItem::forUser($request->user()->id)
            ->with('children')
            ->when($request->input('scope'), fn ($q, $s) => $q->where('scope', $s))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->boolean('incomplete'), fn ($q) => $q->incomplete())
            ->orderBy('due_at')
            ->orderBy('sort_order')
            ->get();

        return $this->respondSuccess(PlannerItemResource::collection($items));
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);

        $item = PlannerItem::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));

        return $this->respondCreated(PlannerItemResource::make($item));
    }

    public function show(Request $request, PlannerItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return $this->respondError('Item not found.', 404);
        }
        return $this->respondSuccess(PlannerItemResource::make($item->load('children')));
    }

    public function update(Request $request, PlannerItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return $this->respondError('Item not found.', 404);
        }

        $item->update($this->validateItem($request, true));

        return $this->respondSuccess(PlannerItemResource::make($item));
    }

    public function destroy(Request $request, PlannerItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return $this->respondError('Item not found.', 404);
        }
        $item->delete();
        return $this->respondNoContent();
    }

    public function toggle(Request $request, PlannerItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return $this->respondError('Item not found.', 404);
        }

        $completed = $item->status === 'completed';
        $item->update([
            'status' => $completed ? 'pending' : 'completed',
            'completed_at' => $completed ? null : now(),
        ]);

        return $this->respondSuccess(PlannerItemResource::make($item));
    }

    public function tree(Request $request)
    {
        $items = PlannerItem::forUser($request->user()->id)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('due_at')
            ->orderBy('sort_order')
            ->get();

        return $this->respondSuccess(PlannerItemResource::collection($items));
    }

    public function agenda(Request $request)
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : now()->startOfDay();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : $from->copy()->addDays(6)->endOfDay();

        $items = PlannerItem::forUser($request->user()->id)
            ->with('children')
            ->incomplete()
            ->dueBetween($from, $to)
            ->orderBy('due_at')
            ->orderBy('priority', 'desc')
            ->get();

        return $this->respondSuccess(PlannerItemResource::collection($items));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function validateItem(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['sometimes', 'in:day,week,month,quarter,year'],
            'parent_id' => ['nullable', 'exists:planner_items,id'],
            'due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', PlannerItem::STATUSES)],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'recurrence_rule' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];

        return $request->validate($rules);
    }
}