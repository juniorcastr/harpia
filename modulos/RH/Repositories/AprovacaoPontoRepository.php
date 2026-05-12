<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\AprovacaoPonto;

class AprovacaoPontoRepository extends BaseRepository
{
    public function __construct(AprovacaoPonto $aprovacaoPonto)
    {
        $this->model = $aprovacaoPonto;
    }
}
