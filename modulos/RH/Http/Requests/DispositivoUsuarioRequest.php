<?php

namespace Modulos\RH\Http\Requests;

use Modulos\Core\Http\Request\BaseRequest;

class DispositivoUsuarioRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'dis_id' => 'required|integer|exists:reh_dispositivos_acesso,dis_id',
            'nome' => 'nullable|string|min:3|max:100',
            'registration' => 'nullable|string|max:50',
            'col_id' => 'nullable|integer|exists:reh_colaboradores,col_id',
            'foto' => 'nullable|image|max:4096',
        ];

        $route = $this->route();
        $routeName = $route ? $route->getName() : null;

        if ($routeName === 'rh.dispositivo-usuarios.create') {
            $rules['col_id'] = 'required|integer|exists:reh_colaboradores,col_id';
        }

        if ($routeName === 'rh.dispositivo-usuarios.atualizar-foto') {
            $rules['foto'] = 'required|image|max:4096';
        }

        return $rules;
    }
}
