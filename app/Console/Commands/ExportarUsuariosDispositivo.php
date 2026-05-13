<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\ExportacaoUsuariosDispositivoService;

class ExportarUsuariosDispositivo extends Command
{
    protected $signature = 'ponto:exportar-usuarios {dis_id?}';

    protected $description = 'Exporta colaboradores ativos para CSV compatível com Control iD';

    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private ExportacaoUsuariosDispositivoService $exportacaoService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $dispositivoId = $this->argument('dis_id');
        $dispositivo = null;

        if ($dispositivoId) {
            $dispositivo = $this->dispositivoRepository->buscarAtivo((int) $dispositivoId);

            if (!$dispositivo) {
                throw new InvalidArgumentException('Dispositivo ativo não encontrado para exportação.');
            }
        }

        $conteudo = $this->exportacaoService->gerarCsv($dispositivo);
        $nomeArquivo = $this->exportacaoService->nomeArquivo($dispositivo);
        $diretorio = storage_path('app/exports/controlid');

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0775, true);
        }

        $caminho = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
        file_put_contents($caminho, $conteudo);

        $this->info('Arquivo exportado em: ' . $caminho);
    }
}
