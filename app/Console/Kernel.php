<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('monitoring:health-check-applications')->everyFiveMinutes();
        $schedule->command('monitoring:collect-host-metrics')->everyFiveMinutes();
        $schedule->command('billing:process-cycles')->dailyAt('00:30');
        $schedule->command('billing:enforce-suspensions')->dailyAt(config('billing.suspend_run_at', '09:05'));
        $schedule->command('gateway:prune-orphans')->dailyAt('09:10');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
