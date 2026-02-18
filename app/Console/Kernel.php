<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ImportaDatiEsterni::class,
    ];

    /**
     * Definisci la schedule dei comandi/artisan.
     */
    protected function schedule(Schedule $schedule)
    {
        // Qui inserisci la tua schedule, ad esempio:
        $schedule->command('app:importa-dati-esterni')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/importa_dati_esterni.log'));
    }

    /**
     * Registra i comandi per la tua applicazione.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
