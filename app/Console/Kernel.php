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
        // Jalankan pembersihan hold booking kedaluwarsa setiap menit
        $schedule->command('bookings:expire')->everyMinute();

        // Jalankan pemeriksaan notifikasi perpanjangan bulanan setiap jam
        $schedule->command('bookings:notify-renewal')->hourly();
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
