<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:notifications')->everyMinute()->withoutOverlapping();
        $schedule->command('ingest:data')->hourly()->withoutOverlapping();
        $schedule->command('gpt:roundscore')->hourly()->withoutOverlapping();


        // Regular maintenance command, safe to run daily
        $schedule->command('telescope:prune')->daily();

        // This is your long-running task. It's set to run daily and should not overlap with itself.
        // This is especially important here because it's a long-running task.
        $schedule->command('ingest:data', ['--longRunning'])->daily()->withoutOverlapping();
    }


    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
