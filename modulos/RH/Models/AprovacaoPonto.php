<?php

namespace Modulos\RH\Models;

use Modulos\Core\Model\BaseModel;

class AprovacaoPonto extends BaseModel
{
    protected $table = 'reh_aprovacoes_ponto';

    protected $primaryKey = 'app_id';

    protected $fillable = [
        'app_eva_id',
        'app_aprovador_col_id',
        'app_data_aprovacao',
        'app_status',
        'app_motivo',
        'app_hora_ajustada',
    ];

    protected $searchable = [
        'app_status' => '=',
    ];

    public function evento()
    {
        return $this->belongsTo('Modulos\RH\Models\EventoAcesso', 'app_eva_id', 'eva_id');
    }

    public function aprovador()
    {
        return $this->belongsTo('Modulos\RH\Models\Colaborador', 'app_aprovador_col_id', 'col_id');
    }
}
