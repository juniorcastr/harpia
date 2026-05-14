<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modulos\RH\Repositories\EventoAcessoRepository;

class LimparEventosAntigos extends Command
{
    protected $signature = 'ponto:limpar-eventos-antigos {--days=30}';

    protected $description = 'Remove eventos antigos de baixo valor operacional, mantendo os processados e aprovados';

    public function __construct(
        private EventoAcessoRepository $eventoRepository
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $dias = max(1, (int) $this->option('days'));
        $limite = Carbon::now()->subDays($dias);

        $removidos = $this->eventoRepository->limparAntigos(['bruto', 'duplicado'], $limite);

        $this->info(sprintf(
            'Limpeza concluída: %d eventos removidos anteriores a %s.',
            $removidos,
            $limite->format('Y-m-d H:i:s')
        ));
    }
}
