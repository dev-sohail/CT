<?php

namespace App\Domains\FileAutomationWatcher\Providers;

use App\Domains\FileAutomationWatcher\Console\RunFileWatcherCommand;
use App\Domains\FileAutomationWatcher\Services\FileWatcherService;
use Illuminate\Support\ServiceProvider;

class FileWatcherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileWatcherService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RunFileWatcherCommand::class]);
        }
    }
}