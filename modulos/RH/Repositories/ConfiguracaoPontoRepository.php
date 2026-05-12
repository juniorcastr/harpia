<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\ConfiguracaoPonto;

class ConfiguracaoPontoRepository extends BaseRepository
{
    public function __construct(ConfiguracaoPonto $configuracaoPonto)
    {
        $this->model = $configuracaoPonto;
    }
}
