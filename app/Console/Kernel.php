<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Schedule the auto-assign clubs command to run daily
        $schedule->command('clubs:auto-assign')->daily(); // Runs every day at midnight
    
    }
}