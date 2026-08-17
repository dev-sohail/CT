<?php

namespace App\Domains\ScheduledTaskAndCronManager\Services;

use App\Domains\ScheduledTaskAndCronManager\Models\ScheduledTask;
use App\Domains\ScheduledTaskAndCronManager\Models\ScheduledTaskLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class TaskScheduler
{
    public function __construct(private CronParser $parser)
    {
    }

    public function computeNextRun(ScheduledTask $task): ?Carbon
    {
        if (!$task->is_active || !$this->parser->isExpressionValid($task->cron_expression)) {
            return null;
        }
        return $this->parser->nextRun($task->cron_expression, now());
    }

    public function refreshNextRun(ScheduledTask $task): ScheduledTask
    {
        $task->update(['next_run_at' => $this->computeNextRun($task)]);
        return $task;
    }

    public function run(ScheduledTask $task): ScheduledTaskLog
    {
        $status = 'success';
        $output = '';

        try {
            Artisan::call($task->command, $task->arguments ?? []);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            $status = 'failed';
            $output = $e->getMessage();
        }

        $task->update([
            'last_status' => $status,
            'last_output' => $output,
            'last_run_at' => now(),
        ]);

        $log = ScheduledTaskLog::create([
            'scheduled_task_id' => $task->id,
            'status' => $status,
            'output' => $output,
        ]);

        $this->refreshNextRun($task);

        return $log;
    }

    public function runDueTasks(): array
    {
        $logs = [];
        $now = now();

        $due = ScheduledTask::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', $now);
            })
            ->get();

        foreach ($due as $task) {
            $logs[] = $this->run($task);
        }

        return $logs;
    }
}