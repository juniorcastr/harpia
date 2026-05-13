<?php

namespace Modulos\RH\Repositories;

use Illuminate\Support\Collection;
use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\MapeamentoDispositivo;

class MapeamentoDispositivoRepository extends BaseRepository
{
    public function __construct(MapeamentoDispositivo $mapeamentoDispositivo)
    {
        $this->model = $mapeamentoDispositivo;
    }

    public function listarPorDispositivo(int $dispositivoId): Collection
    {
        return $this->queryBase()
            ->where('reh_mapeamento_dispositivo.map_dis_id', $dispositivoId)
            ->orderBy('reh_mapeamento_dispositivo.map_nome_dispositivo')
            ->get();
    }

    public function listarPorDispositivoEUsuarios(int $dispositivoId, array $userIds): Collection
    {
        $query = $this->queryBase()
            ->where('reh_mapeamento_dispositivo.map_dis_id', $dispositivoId);

        if (!empty($userIds)) {
            $query->whereIn('reh_mapeamento_dispositivo.map_user_id', $userIds);
        }

        return $query->get();
    }

    public function buscarPorDispositivoEUsuario(int $dispositivoId, string $userId): ?MapeamentoDispositivo
    {
        return $this->model
            ->where('map_dis_id', $dispositivoId)
            ->where('map_user_id', $userId)
            ->first();
    }

    public function buscarPorDispositivoEColaborador(int $dispositivoId, int $colaboradorId, ?string $ignorarUserId = null): ?MapeamentoDispositivo
    {
        $query = $this->model
            ->where('map_dis_id', $dispositivoId)
            ->where('map_col_id', $colaboradorId)
            ->where('map_ativo', true);

        if ($ignorarUserId !== null) {
            $query->where('map_user_id', '<>', $ignorarUserId);
        }

        return $query->first();
    }

    private function queryBase()
    {
        return $this->model
            ->leftJoin('reh_colaboradores', 'reh_mapeamento_dispositivo.map_col_id', '=', 'reh_colaboradores.col_id')
            ->leftJoin('gra_pessoas', 'reh_colaboradores.col_pes_id', '=', 'gra_pessoas.pes_id')
            ->select([
                'reh_mapeamento_dispositivo.*',
                'gra_pessoas.pes_nome as colaborador_nome',
                'reh_colaboradores.col_status as colaborador_status',
            ]);
    }
}
