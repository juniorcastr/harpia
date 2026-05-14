<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\ColetaEventosDispositivoService;

class ColetarEventosControlId extends Command
{
    protected $signature = 'ponto:coletar-eventos {dis_id?} {--limit=}';

    protected $description = 'Coleta logs de acesso diretamente do Control iD como fallback ao monitor em tempo real';

    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private ColetaEventosDispositivoService $coletaEventosDispositivoService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $dispositivoId = $this->argument('dis_id');
        $limit = (int) ($this->option('limit') ?: config('ponto.polling.limit', 500));
        $dispositivos = [];

        if ($dispositivoId) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $dispositivoId);

            if (!$dispositivo) {
                throw new InvalidArgumentException('Dispositivo ativo não encontrado para coleta de eventos.');
            }

            $dispositivos[] = $dispositivo;
        } else {
            $dispositivos = $this->dispositivoRepository->listarAtivos()->all();
        }

        foreach ($dispositivos as $dispositivo) {
            $resultado = $this->coletaEventosDispositivoService->coletarNovosEventos($dispositivo, $limit);

            $this->info(sprintf(
                '[%s] lidos=%d processado=%d erro=%d duplicado=%d ignorado=%d ultimo_id=%d',
                $dispositivo->dis_nome,
                $resultado['lidos'],
                $resultado['processado'],
                $resultado['erro'],
                $resultado['duplicado'],
                $resultado['ignorado'],
                $resultado['ultimo_id_atual']
            ));
        }
    }
}
