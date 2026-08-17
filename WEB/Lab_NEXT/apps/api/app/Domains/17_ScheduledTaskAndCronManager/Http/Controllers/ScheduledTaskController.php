<?php

namespace App\Domains\ScheduledTaskAndCronManager\Http\Controllers;

use App\Domains\ScheduledTaskAndCronManager\Http\Resources\ScheduledTaskLogResource;
use App\Domains\ScheduledTaskAndCronManager\Http\Resources\ScheduledTaskResource;
use App\Domains\ScheduledTaskAndCronManager\Models\ScheduledTask;
use App\Domains\ScheduledTaskAndCronManager\Services\CronParser;
use App\Domains\ScheduledTaskAndCronManager\Services\TaskScheduler;
use App\Domains\SharedRestApiGateway\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ScheduledTaskController extends ApiController
{
    public function __construct(private TaskScheduler $scheduler, private CronParser $parser)
    {
    }

    public function validateExpression(Request $request)
    {
        $expression = $request->validate(['expression' => ['required', 'string']])['expression'];
        return $this->respondSuccess([
            'valid' => $this->parser->isExpressionValid($expression),
            'next_run_at' => $this->parser->nextRun($expression, now())?->toIso8601String(),
        ]);
    }

    public function index(Request $request)
    {
        $tasks = ScheduledTask::where('user_id', $request->user()->id)->orderByDesc('updated_at')->get();
        return $this->respondSuccess(ScheduledTaskResource::collection($tasks));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string', 'max:255'],
            'cron_expression' => ['required', 'string', 'max:64'],
            'is_active' => ['boolean'],
            'arguments' => ['nullable', 'array'],
        ]);

        if (!$this->parser->isExpressionValid($validated['cron_expression'])) {
            return $this->respondError('Invalid cron expression.', 422);
        }

        $task = ScheduledTask::create(array_merge(
            ['user_id' => $request->user()->id],
            $validated
        ));
        $task->refresh();
        $this->scheduler->refreshNextRun($task);

        return $this->respondCreated(ScheduledTaskResource::make($task));
    }

    public function show(Request $request, ScheduledTask $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return $this->respondError('Scheduled task not found.', 404);
        }
        return $this->respondSuccess(ScheduledTaskResource::make($task));
    }

    public function update(Request $request, ScheduledTask $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return $this->respondError('Scheduled task not found.', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'command' => ['sometimes', 'string', 'max:255'],
            'cron_expression' => ['sometimes', 'string', 'max:64'],
            'is_active' => ['boolean'],
            'arguments' => ['nullable', 'array'],
        ]);

        if (isset($validated['cron_expression']) && !$this->parser->isExpressionValid($validated['cron_expression'])) {
            return $this->respondError('Invalid cron expression.', 422);
        }

        $task->update($validated);
        $this->scheduler->refreshNextRun($task);

        return $this->respondSuccess(ScheduledTaskResource::make($task));
    }

    public function destroy(Request $request, ScheduledTask $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return $this->respondError('Scheduled task not found.', 404);
        }
        $task->delete();
        return $this->respondNoContent();
    }

    public function run(Request $request, ScheduledTask $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return $this->respondError('Scheduled task not found.', 404);
        }

        $log = $this->scheduler->run($task);

        return $this->respondSuccess([
            'task' => ScheduledTaskResource::make($task->fresh()),
            'log' => ScheduledTaskLogResource::make($log),
        ]);
    }

    public function logs(Request $request, ScheduledTask $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return $this->respondError('Scheduled task not found.', 404);
        }

        $logs = $task->logs()->limit(20)->get();
        return $this->respondSuccess(ScheduledTaskLogResource::collection($logs));
    }
}