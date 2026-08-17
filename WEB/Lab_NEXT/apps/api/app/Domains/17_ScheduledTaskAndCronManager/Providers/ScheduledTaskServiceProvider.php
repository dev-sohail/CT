<?php

namespace App\Domains\ScheduledTaskAndCronManager\Providers;

use App\Domains\ScheduledTaskAndCronManager\Console\RunDueTasksCommand;
use App\Domains\ScheduledTaskAndCronManager\Services\CronParser;
use App\Domains\ScheduledTaskAndCronManager\Services\TaskScheduler;
use Illuminate\Support\ServiceProvider;

class ScheduledTaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CronParser::class);
        $this->app->singleton(TaskScheduler::class, fn ($app) => new TaskScheduler($app->make(CronParser::class)));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RunDueTasksCommand::class]);
        }
    }
}