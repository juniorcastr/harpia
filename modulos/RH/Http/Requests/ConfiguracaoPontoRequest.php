<?php

namespace Modulos\RH\Http\Requests;

use Closure;
use Modulos\Core\Http\Request\BaseRequest;

class ConfiguracaoPontoRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'janela_inicio' => 'required|date_format:H:i',
            'janela_fim' => 'required|date_format:H:i',
            'anti_duplicidade_seg' => 'required|integer|min:1|max:3600',
            'retry_max_tentativas' => 'required|integer|min:0|max:20',
            'retry_delay_min' => 'required|integer|min:0|max:1440',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (($this->get('janela_inicio') ?? '') >= ($this->get('janela_fim') ?? '')) {
                    $validator->errors()->add('janela_fim', 'O horário final precisa ser maior que o horário inicial.');
                }
            },
        ];
    }
}
