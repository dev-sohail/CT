<?php

namespace App\Domains\HomeMaintenanceScheduler\Http\Controllers;

use App\Domains\HomeMaintenanceScheduler\Http\Resources\MaintenanceTaskResource;
use App\Domains\HomeMaintenanceScheduler\Models\MaintenanceTask;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class MaintenanceTaskController extends ApiController
{
    public function index(Request $request)
    {
        $items = MaintenanceTask::forUser($request->user()->id)->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))->orderBy('due_on')->paginate($request->integer('per_page', 25));
        return $this->respondPaginated($items, MaintenanceTaskResource::class);
    }
    public function store(Request $request)
    {
        $task = MaintenanceTask::create(array_merge(['user_id' => $request->user()->id], $this->validateTask($request)));
        return $this->respondCreated(MaintenanceTaskResource::make($task)->toArray($request));
    }
    public function show(Request $request, MaintenanceTask $maintenanceTask) { $this->authorizeOwner($request, $maintenanceTask); return $this->respondSuccess(MaintenanceTaskResource::make($maintenanceTask)->toArray($request)); }
    public function update(Request $request, MaintenanceTask $maintenanceTask) { $this->authorizeOwner($request, $maintenanceTask); $maintenanceTask->update($this->validateTask($request, true)); return $this->respondSuccess(MaintenanceTaskResource::make($maintenanceTask)->toArray($request)); }
    public function destroy(Request $request, MaintenanceTask $maintenanceTask) { $this->authorizeOwner($request, $maintenanceTask); $maintenanceTask->delete(); return $this->respondNoContent(); }
    public function complete(Request $request, MaintenanceTask $maintenanceTask) { $this->authorizeOwner($request, $maintenanceTask); $maintenanceTask->update(['status' => 'completed', 'completed_on' => now()->toDateString()]); return $this->respondSuccess(MaintenanceTaskResource::make($maintenanceTask)->toArray($request)); }
    public function stats(Request $request) { $items = MaintenanceTask::forUser($request->user()->id)->get(); return $this->respondSuccess(['total' => $items->count(), 'planned' => $items->where('status', 'planned')->count(), 'completed' => $items->where('status', 'completed')->count(), 'overdue' => $items->filter(fn (MaintenanceTask $t) => $t->status !== 'completed' && $t->due_on && $t->due_on->lt(now()->startOfDay()))->count(), 'total_cost' => (float) $items->sum('cost')]); }
    private function validateTask(Request $request, bool $partial = false): array { return $request->validate(['title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'category' => ['nullable', 'string', 'max:64'], 'location' => ['nullable', 'string', 'max:128'], 'due_on' => ['nullable', 'date'], 'completed_on' => ['nullable', 'date'], 'recurrence_days' => ['nullable', 'integer', 'min:1'], 'cost' => ['nullable', 'numeric', 'min:0'], 'status' => ['sometimes', 'in:planned,in_progress,completed,skipped'], 'priority' => ['sometimes', 'in:low,medium,high'], 'notes' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]); }
    private function authorizeOwner(Request $request, MaintenanceTask $task): void { if ($task->user_id !== $request->user()->id) abort(404); }
}
