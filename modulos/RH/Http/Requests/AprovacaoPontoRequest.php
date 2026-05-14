<?php

namespace Modulos\RH\Http\Requests;

use Modulos\Core\Http\Request\BaseRequest;

class AprovacaoPontoRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'motivo' => $this->routeIs('rh.aprovacoes-ponto.reprovar') ? 'required|string|max:2000' : 'nullable|string|max:2000',
        ];
    }
}
