<?php

namespace App\Domains\ScheduledTaskAndCronManager\Console;

use App\Domains\ScheduledTaskAndCronManager\Services\TaskScheduler;
use Illuminate\Console\Command;

class RunDueTasksCommand extends Command
{
    protected $signature = 'cron:run';

    protected $description = 'Run all scheduled tasks that are due';

    public function handle(TaskScheduler $scheduler): int
    {
        $logs = $scheduler->runDueTasks();

        $this->info('Ran ' . count($logs) . ' scheduled tasks.');

        return self::SUCCESS;
    }
}