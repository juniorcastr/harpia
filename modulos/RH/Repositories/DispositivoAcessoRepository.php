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
}
