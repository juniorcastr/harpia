<?php

namespace Modulos\RH\Http\Controllers;

use App\Exports\RegistrosPontoExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\RegistrosPontoRepository;
use Modulos\RH\Repositories\SetorRepository;

class RegistrosPontoController extends BaseController
{
    public function __construct(
        private RegistrosPontoRepository $registrosRepository,
        private ColaboradorRepository $colaboradorRepository,
        private SetorRepository $setorRepository,
        private Excel $excel
    ) {
    }

    public function getIndex(Request $request)
    {
        $registros = $this->registrosRepository->buscarResumo($request->all());
        $colaboradores = $this->colaboradorRepository->listarAtivosParaSelecao();
        $setores = $this->setorRepository->lists('set_id', 'set_descricao');
        $origens = ['idface', 'home_office'];
        $statusList = ['processado', 'pendente', 'aprovado', 'reprovado', 'erro'];

        return view('RH::registros_ponto.index', compact('registros', 'colaboradores', 'setores', 'origens', 'statusList'));
    }

    public function getDetalhes($colaboradorId, $data)
    {
        $colaborador = $this->colaboradorRepository->buscarAtivoComPessoa((int) $colaboradorId);

        if (!$colaborador) {
            flash()->error('Colaborador não encontrado para o detalhamento.');
            return redirect()->route('rh.registros-ponto.index');
        }

        $eventos = $this->registrosRepository->buscarDetalhes((int) $colaboradorId, (string) $data);
        $totalHoras = $this->registrosRepository->calcularTotalHoras($eventos);

        return view('RH::registros_ponto.detalhes', compact('colaborador', 'eventos', 'data', 'totalHoras'));
    }

    public function getExport(Request $request)
    {
        $registros = $this->registrosRepository->buscarResumo($request->all(), false);

        $dados = [[
            'Nome',
            'Matrícula',
            'Setor',
            'Data',
            'Entrada',
            'Saída',
            'Total Horas',
            'Origem',
            'Status',
            'Eventos',
        ]];

        foreach ($registros as $registro) {
            $dados[] = [
                $registro->pes_nome,
                $registro->col_id,
                $registro->set_descricao ?: '-',
                $registro->rpo_data,
                $registro->entrada_formatada,
                $registro->saida_formatada,
                $registro->total_horas,
                $registro->origem_label,
                $registro->status_label,
                $registro->total_eventos,
            ];
        }

        return $this->excel->download(
            new RegistrosPontoExport($dados),
            'registros_ponto_' . date('Y-m-d') . '.xlsx'
        );
    }
}
