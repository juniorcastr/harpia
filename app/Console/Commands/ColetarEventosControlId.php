<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\ControlIdApiClient;
use Modulos\RH\Services\EventoAcessoService;

class ColetarEventosControlId extends Command
{
    protected $signature = 'ponto:coletar-eventos {dis_id?} {--limit=100}';

    protected $description = 'Coleta logs de acesso diretamente do Control iD como fallback ao monitor em tempo real';

    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private ControlIdApiClient $apiClient,
        private EventoAcessoService $eventoAcessoService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $dispositivoId = $this->argument('dis_id');
        $limit = (int) $this->option('limit');
        $dispositivos = [];

        if ($dispositivoId) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $dispositivoId);

            if (!$dispositivo) {
                throw new InvalidArgumentException('Dispositivo ativo não encontrado para coleta de eventos.');
            }

            $dispositivos[] = $dispositivo;
        } else {
            $dispositivos = $this->dispositivoRepository->all()
                ->where('dis_status', 'ativo')
                ->values()
                ->all();
        }

        foreach ($dispositivos as $dispositivo) {
            $resposta = $this->apiClient->loadObjects($dispositivo, 'access_logs', [
                'limit' => $limit,
            ]);

            $logs = $this->extrairLogs($resposta);
            $resultado = $this->eventoAcessoService->processarLoteAccessLogs($dispositivo, $logs, [
                'ip' => $dispositivo->dis_ip,
                'user_agent' => 'polling-controlid',
            ]);

            $this->info(sprintf(
                '[%s] processado=%d erro=%d duplicado=%d ignorado=%d',
                $dispositivo->dis_nome,
                $resultado['processado'],
                $resultado['erro'],
                $resultado['duplicado'],
                $resultado['ignorado']
            ));
        }
    }

    private function extrairLogs(array $resposta): array
    {
        foreach (['access_logs', 'values', 'objects'] as $key) {
            if (isset($resposta[$key]) && is_array($resposta[$key])) {
                return array_values(array_filter($resposta[$key], 'is_array'));
            }
        }

        return array_values(array_filter($resposta, 'is_array'));
    }
}
