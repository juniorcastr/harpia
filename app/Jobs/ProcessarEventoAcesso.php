<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Services\EventoAcessoService;

class ProcessarEventoAcesso extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private int $dispositivoId,
        private array $evento,
        private array $contexto = []
    ) {
    }

    public function handle(EventoAcessoService $eventoAcessoService): void
    {
        $dispositivo = DispositivoAcesso::find($this->dispositivoId);

        if (!$dispositivo || $dispositivo->dis_status !== 'ativo') {
            return;
        }

        $eventoAcessoService->processarEventoMonitor($dispositivo, $this->evento, $this->contexto);
    }
}
