<?php

namespace Modulos\RH\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modulos\RH\Models\EventoAcesso;

class RegistrosPontoRepository
{
    public function __construct(
        private EventoAcesso $model
    ) {
    }

    public function buscarResumo(array $filtros = [], bool $paginar = true)
    {
        $query = DB::table('reh_eventos_acesso as eva')
            ->join('reh_colaboradores as col', 'col.col_id', '=', 'eva.eva_col_id')
            ->join('gra_pessoas as pes', 'pes.pes_id', '=', 'col.col_pes_id')
            ->leftJoin('reh_colaboradores_funcoes as cfn', function ($join) {
                $join->on('cfn.cfn_col_id', '=', 'col.col_id')
                    ->whereNull('cfn.cfn_data_fim');
            })
            ->leftJoin('reh_setores as set', 'set.set_id', '=', 'cfn.cfn_set_id')
            ->select([
                'col.col_id',
                'pes.pes_nome',
                'set.set_descricao',
                DB::raw('DATE(eva.eva_data_hora) as rpo_data'),
                DB::raw("MIN(CASE WHEN eva.eva_tipo = 'entrada' THEN eva.eva_data_hora END) as primeira_entrada"),
                DB::raw("MAX(CASE WHEN eva.eva_tipo = 'saida' THEN eva.eva_data_hora END) as ultima_saida"),
                DB::raw('GROUP_CONCAT(DISTINCT eva.eva_origem) as origens'),
                DB::raw('GROUP_CONCAT(DISTINCT eva.eva_status) as status_lista'),
                DB::raw('COUNT(eva.eva_id) as total_eventos'),
            ])
            ->groupBy('col.col_id', 'pes.pes_nome', 'set.set_descricao', DB::raw('DATE(eva.eva_data_hora)'));

        $this->aplicarFiltrosResumo($query, $filtros);

        $query->orderByDesc(DB::raw('DATE(eva.eva_data_hora)'))
            ->orderBy('pes.pes_nome');

        $resultado = $paginar ? $query->paginate(15) : $query->get();
        $colecao = $paginar ? $resultado->getCollection() : $resultado;

        $colecao->transform(function ($registro) {
            $detalhes = $this->buscarDetalhes($registro->col_id, $registro->rpo_data);
            $registro->origem_label = $this->resolverOrigemLabel((string) $registro->origens);
            $registro->status_label = $this->resolverStatusLabel((string) $registro->status_lista);
            $registro->entrada_formatada = $registro->primeira_entrada
                ? Carbon::parse($registro->primeira_entrada)->format('H:i:s')
                : '-';
            $registro->saida_formatada = $registro->ultima_saida
                ? Carbon::parse($registro->ultima_saida)->format('H:i:s')
                : '-';
            $registro->total_horas = $this->calcularTotalHoras($detalhes);

            return $registro;
        });

        return $resultado;
    }

    public function buscarDetalhes(int $colaboradorId, string $data): Collection
    {
        $inicio = Carbon::parse($data)->startOfDay()->toDateTimeString();
        $fim = Carbon::parse($data)->endOfDay()->toDateTimeString();

        return $this->model->newQuery()
            ->with(['dispositivo', 'colaborador.pessoa'])
            ->where('eva_col_id', $colaboradorId)
            ->whereBetween('eva_data_hora', [$inicio, $fim])
            ->orderBy('eva_data_hora')
            ->get();
    }

    public function calcularTotalHoras(Collection $eventos): string
    {
        $entradaAtual = null;
        $totalSegundos = 0;

        foreach ($eventos as $evento) {
            if ($evento->eva_tipo === 'entrada') {
                $entradaAtual = Carbon::parse($evento->eva_data_hora);
                continue;
            }

            if ($evento->eva_tipo === 'saida' && $entradaAtual) {
                $saida = Carbon::parse($evento->eva_data_hora);

                if ($saida->greaterThan($entradaAtual)) {
                    $totalSegundos += abs($saida->diffInSeconds($entradaAtual));
                }

                $entradaAtual = null;
            }
        }

        if ($totalSegundos <= 0) {
            return '-';
        }

        $horas = intdiv($totalSegundos, 3600);
        $minutos = intdiv($totalSegundos % 3600, 60);

        return sprintf('%02d:%02d', $horas, $minutos);
    }

    private function aplicarFiltrosResumo($query, array $filtros): void
    {
        $inicio = !empty($filtros['data_inicio'])
            ? $this->normalizarDataFiltro((string) $filtros['data_inicio'])->startOfDay()->toDateTimeString()
            : Carbon::now()->startOfMonth()->toDateTimeString();
        $fim = !empty($filtros['data_fim'])
            ? $this->normalizarDataFiltro((string) $filtros['data_fim'])->endOfDay()->toDateTimeString()
            : Carbon::now()->endOfMonth()->toDateTimeString();

        $query->whereBetween('eva.eva_data_hora', [$inicio, $fim]);

        if (!empty($filtros['col_id'])) {
            $query->where('col.col_id', $filtros['col_id']);
        }

        if (!empty($filtros['setores'])) {
            $query->whereIn('set.set_id', array_filter((array) $filtros['setores']));
        }

        if (!empty($filtros['origem'])) {
            $query->where('eva.eva_origem', $filtros['origem']);
        }

        if (!empty($filtros['status'])) {
            $query->where('eva.eva_status', $filtros['status']);
        }
    }

    private function normalizarDataFiltro(string $valor): Carbon
    {
        $valor = trim($valor);

        foreach (['d/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor);
            } catch (\Throwable $exception) {
            }
        }

        return Carbon::parse($valor);
    }

    private function resolverOrigemLabel(string $origens): string
    {
        $lista = array_filter(array_map('trim', explode(',', $origens)));

        if (count($lista) > 1) {
            return 'misto';
        }

        return $lista[0] ?? '-';
    }

    private function resolverStatusLabel(string $statusLista): string
    {
        $lista = array_filter(array_map('trim', explode(',', $statusLista)));

        foreach (['erro', 'reprovado', 'pendente', 'aprovado', 'processado', 'duplicado', 'bruto'] as $prioridade) {
            if (in_array($prioridade, $lista, true)) {
                return $prioridade;
            }
        }

        return '-';
    }
}
