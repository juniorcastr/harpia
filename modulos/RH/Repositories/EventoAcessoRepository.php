<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\EventoAcesso;

class EventoAcessoRepository extends BaseRepository
{
    public function __construct(EventoAcesso $eventoAcesso)
    {
        $this->model = $eventoAcesso;
    }
}
