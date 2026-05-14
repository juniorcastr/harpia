<?php

namespace Modulos\RH\Repositories;

use Illuminate\Support\Collection;
use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\DispositivoAcesso;

class DispositivoAcessoRepository extends BaseRepository
{
    public function __construct(DispositivoAcesso $dispositivoAcesso)
    {
        $this->model = $dispositivoAcesso;
    }

    public function listarAtivosParaSelecao()
    {
        return $this->model
            ->where('dis_status', 'ativo')
            ->orderBy('dis_nome')
            ->pluck('dis_nome', 'dis_id');
    }

    public function listarAtivos(): Collection
    {
        return $this->model
            ->where('dis_status', 'ativo')
            ->orderBy('dis_nome')
            ->get();
    }

    public function listarAtivosPorIds(array $dispositivoIds): Collection
    {
        $dispositivoIds = array_values(array_unique(array_filter(array_map('intval', $dispositivoIds))));

        if (empty($dispositivoIds)) {
            return collect();
        }

        return $this->model
            ->where('dis_status', 'ativo')
            ->whereIn('dis_id', $dispositivoIds)
            ->orderBy('dis_nome')
            ->get();
    }

    public function buscarAtivo(int $dispositivoId): ?DispositivoAcesso
    {
        return $this->model
            ->where('dis_id', $dispositivoId)
            ->where('dis_status', 'ativo')
            ->first();
    }
}
