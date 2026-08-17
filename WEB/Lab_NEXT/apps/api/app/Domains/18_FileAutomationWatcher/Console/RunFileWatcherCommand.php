<?php

namespace App\Domains\FileAutomationWatcher\Console;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;
use App\Domains\FileAutomationWatcher\Models\FileWatcherRule;
use App\Domains\FileAutomationWatcher\Services\FileWatcherService;
use Illuminate\Console\Command;

class RunFileWatcherCommand extends Command
{
    protected $signature = 'file-watcher:run {--user= : Only run rules for this user id}';

    protected $description = 'Run all active file automation watcher rules';

    public function handle(FileWatcherService $watcher): int
    {
        $query = FileWatcherRule::where('is_active', true);
        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $processed = 0;
        foreach ($query->get() as $rule) {
            $processed += count($watcher->run($rule));
        }

        $this->info("Processed {$processed} files.");

        return self::SUCCESS;
    }
}