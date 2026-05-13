<?php

namespace Modulos\RH\Repositories;

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

    public function buscarAtivo(int $dispositivoId): ?DispositivoAcesso
    {
        return $this->model
            ->where('dis_id', $dispositivoId)
            ->where('dis_status', 'ativo')
            ->first();
    }
}
