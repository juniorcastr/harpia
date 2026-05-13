<?php

namespace Modulos\RH\Http\Requests;

use Modulos\Core\Http\Request\BaseRequest;

class MonitorNotificacaoRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'device_id' => 'required',
            'object_changes' => 'required|array|min:1',
            'object_changes.*.object' => 'required|string',
            'object_changes.*.type' => 'required|string',
            'object_changes.*.values' => 'required|array',
            'object_changes.*.values.id' => 'required',
            'object_changes.*.values.time' => 'required',
            'object_changes.*.values.event' => 'required',
            'object_changes.*.values.user_id' => 'nullable',
            'object_changes.*.values.device_id' => 'nullable',
        ];
    }
}
