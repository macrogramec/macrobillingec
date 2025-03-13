<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('documentos:procesar-pendientes')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/documentos-cron.log'));
    }
}
