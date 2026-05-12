<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\GestorSetor;

class GestorSetorRepository extends BaseRepository
{
    public function __construct(GestorSetor $gestorSetor)
    {
        $this->model = $gestorSetor;
    }
}
