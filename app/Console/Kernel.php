<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('users:update-active-status')
            ->cron('30 23 28-31 * *')
            ->when(function () {
                return now()->isLastOfMonth();
            })
            ->withoutOverlapping();

        $schedule->command('backup:mail')->dailyAt('00:00')->withoutOverlapping()->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
