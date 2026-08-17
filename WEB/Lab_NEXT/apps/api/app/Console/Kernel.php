<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('ctlab:backup')
            ->dailyAt((string) config('backup.scheduled_at', '02:00'))
            ->appendOutputTo(storage_path('logs/backup.log'))
            ->onFailure(function () {
                report(new \RuntimeException('CTLabs database backup failed.'));
            });

        $schedule->command('rules:run')
            ->hourly()
            ->appendOutputTo(storage_path('logs/rules.log'));
    }
}
