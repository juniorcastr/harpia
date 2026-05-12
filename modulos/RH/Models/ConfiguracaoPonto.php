<?php

namespace Modulos\RH\Models;

use Modulos\Core\Model\BaseModel;

class ConfiguracaoPonto extends BaseModel
{
    protected $table = 'reh_configuracoes_ponto';

    protected $primaryKey = 'cfg_id';

    protected $fillable = [
        'cfg_chave',
        'cfg_valor',
        'cfg_descricao',
    ];

    protected $searchable = [
        'cfg_chave' => 'like',
        'cfg_descricao' => 'like',
    ];
}
