<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\KeapRefresh::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
//        dd('schedule function');
        $schedule->command('keap:refresh')->twiceDaily(2, 14);
    }

    protected function commands()
    {
//        dd('commands function');
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
