<?php

namespace Modulos\RH\Http\Requests;

use Modulos\Core\Http\Request\BaseRequest;

class RegistroPontoRemotoRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'observacao' => 'nullable|string|max:1000',
        ];

        if ($this->is('api/rh/ponto-remoto/*')) {
            $rules['email'] = 'required|email';
            $rules['data_nascimento'] = 'required|date';
        }

        return $rules;
    }
}
