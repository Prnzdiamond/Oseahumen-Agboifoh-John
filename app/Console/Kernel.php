<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Both sync commands run daily in the early hours UTC so they
     * complete before your workday starts. If the Devicon or Lucide
     * repos update overnight, you'll have fresh data by morning.
     *
     * You can also trigger either command manually from the admin panel
     * (Phase 2 adds a button for this) or via SSH:
     *   php artisan technologies:sync
     *   php artisan icons:sync
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync Devicon → technologies table
        // Runs daily at 02:00 UTC
        $schedule->command('technologies:sync')
            ->dailyAt('02:00')
            ->withoutOverlapping()     // Don't run if previous run is still going
            ->runInBackground()        // Don't block other scheduled tasks
            ->appendOutputTo(storage_path('logs/technologies-sync.log'));

        // Sync Lucide → icons table
        // Runs daily at 02:30 UTC (30 min after technologies to avoid overlap)
        // Note: First run fetches all SVGs and takes ~10-15 min depending on
        // connection. Subsequent runs are faster because is_manual records
        // are skipped and SVGs are only refetched if missing.
        $schedule->command('icons:sync')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/icons-sync.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
