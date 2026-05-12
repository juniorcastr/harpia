<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\MapeamentoDispositivo;

class MapeamentoDispositivoRepository extends BaseRepository
{
    public function __construct(MapeamentoDispositivo $mapeamentoDispositivo)
    {
        $this->model = $mapeamentoDispositivo;
    }
}
