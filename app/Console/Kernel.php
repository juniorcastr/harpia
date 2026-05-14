<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ColetarEventosControlId::class,
        // Commands\Inspire::class,
        Commands\ExportarUsuariosDispositivo::class,
        Commands\LimparEventosAntigos::class,
        Commands\ModulosMigrate::class,
        Commands\ModulosSeed::class,
        Commands\ReprocessarEventosFalha::class,
        Commands\SincronizarMapeamentoDispositivo::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (config('ponto.polling.enabled')) {
            $schedule->command(sprintf(
                'ponto:coletar-eventos --limit=%d',
                (int) config('ponto.polling.limit', 500)
            ))
                ->cron((string) config('ponto.polling.cron', '*/15 * * * *'))
                ->withoutOverlapping();
        }
    }
}
