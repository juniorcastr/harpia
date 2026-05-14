<?php

namespace Modulos\RH\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\EventoAcesso;

class EventoAcessoRepository extends BaseRepository
{
    public function __construct(EventoAcesso $eventoAcesso)
    {
        $this->model = $eventoAcesso;
    }

    public function paginateAdmin(array $filtros = [])
    {
        $query = $this->model->newQuery()
            ->from('reh_eventos_acesso as eva')
            ->leftJoin('reh_colaboradores as col', 'col.col_id', '=', 'eva.eva_col_id')
            ->leftJoin('gra_pessoas as pes', 'pes.pes_id', '=', 'col.col_pes_id')
            ->leftJoin('reh_dispositivos_acesso as dis', 'dis.dis_id', '=', 'eva.eva_dis_id')
            ->select([
                'eva.*',
                'pes.pes_nome',
                'dis.dis_nome',
            ]);

        $inicio = !empty($filtros['data_inicio']) ? Carbon::parse($filtros['data_inicio'])->startOfDay()->toDateTimeString() : Carbon::now()->startOfMonth()->toDateTimeString();
        $fim = !empty($filtros['data_fim']) ? Carbon::parse($filtros['data_fim'])->endOfDay()->toDateTimeString() : Carbon::now()->endOfMonth()->toDateTimeString();

        $query->whereBetween('eva.eva_data_hora', [$inicio, $fim]);

        if (!empty($filtros['col_id'])) {
            $query->where('eva.eva_col_id', $filtros['col_id']);
        }

        if (!empty($filtros['dis_id'])) {
            $query->where('eva.eva_dis_id', $filtros['dis_id']);
        }

        if (!empty($filtros['origem'])) {
            $query->where('eva.eva_origem', $filtros['origem']);
        }

        if (!empty($filtros['status'])) {
            $query->where('eva.eva_status', $filtros['status']);
        }

        if (!empty($filtros['tipo'])) {
            $query->where('eva.eva_tipo', $filtros['tipo']);
        }

        if (!empty($filtros['hash'])) {
            $query->where('eva.eva_hash', 'like', '%' . trim((string) $filtros['hash']) . '%');
        }

        return $query
            ->orderByDesc('eva.eva_data_hora')
            ->paginate(20);
    }

    public function buscarDetalhado(int $eventoId): ?EventoAcesso
    {
        return $this->model->newQuery()
            ->with(['colaborador.pessoa', 'dispositivo', 'aprovacoes'])
            ->find($eventoId);
    }

    public function limparAntigos(array $status, Carbon $limite): int
    {
        return $this->model->newQuery()
            ->whereIn('eva_status', $status)
            ->where('created_at', '<', $limite->toDateTimeString())
            ->delete();
    }

    public function buscarFalhasParaReprocessamento(int $limit = 100)
    {
        return $this->model->newQuery()
            ->where('eva_status', 'erro')
            ->orderBy('eva_data_hora')
            ->limit($limit)
            ->get();
    }
}
