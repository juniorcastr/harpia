<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\SincronizacaoUsuariosDispositivoService;

class SincronizarMapeamentoDispositivo extends Command
{
    protected $signature = 'ponto:sincronizar-mapeamento {dis_id?}';

    protected $description = 'Sincroniza usuários do iDFace com a tabela de mapeamento do Harpia';

    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private SincronizacaoUsuariosDispositivoService $sincronizacaoService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $dispositivoId = $this->argument('dis_id');
        $dispositivos = [];

        if ($dispositivoId) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $dispositivoId);

            if (!$dispositivo) {
                throw new InvalidArgumentException('Dispositivo ativo não encontrado para sincronização.');
            }

            $dispositivos[] = $dispositivo;
        } else {
            $dispositivos = $this->dispositivoRepository->all()
                ->where('dis_status', 'ativo')
                ->values()
                ->all();
        }

        foreach ($dispositivos as $dispositivo) {
            $resultado = $this->sincronizacaoService->sincronizar($dispositivo);
            $resumo = $resultado['resumo'];

            $this->info(sprintf(
                '[%s] total=%d vinculados=%d pendentes=%d com_foto=%d sem_foto=%d',
                $dispositivo->dis_nome,
                $resumo['total'],
                $resumo['vinculados'],
                $resumo['pendentes'],
                $resumo['com_foto'],
                $resumo['sem_foto']
            ));
        }
    }
}
