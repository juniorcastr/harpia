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
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'dispositivos_destino' => 'nullable|array',
            'dispositivos_destino.*' => 'integer|exists:reh_dispositivos_acesso,dis_id',
            'cadastrar_em_todos_dispositivos' => 'nullable|boolean',
        ];

        $route = $this->route();
        $routeName = $route ? $route->getName() : null;

        if (in_array($routeName, ['rh.dispositivo-usuarios.create', 'rh.dispositivos-usuarios.create'], true)) {
            $rules['col_id'] = 'required|integer|exists:reh_colaboradores,col_id';
        }

        if (in_array($routeName, ['rh.dispositivo-usuarios.atualizar-foto', 'rh.dispositivos-usuarios.atualizar-foto'], true)) {
            $rules['foto'] = 'required|image|mimes:jpg,jpeg,png|max:2048';
        }

        return $rules;
    }
}
