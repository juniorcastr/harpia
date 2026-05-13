<?php

namespace Modulos\RH\Http\Controllers;

use App\Jobs\ProcessarEventoAcesso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\MonitorNotificacaoRequest;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Services\EventoAcessoService;

class MonitorController extends BaseController
{
    public function __construct(
        private EventoAcessoService $eventoAcessoService
    ) {
    }

    public function receberLogAcesso(MonitorNotificacaoRequest $request): JsonResponse
    {
        $dispositivo = $this->dispositivo($request);
        $disparados = 0;

        foreach ($request->input('object_changes', []) as $objectChange) {
            if (($objectChange['object'] ?? null) !== 'access_logs') {
                continue;
            }

            if (($objectChange['type'] ?? null) !== 'inserted') {
                continue;
            }

            dispatch(new ProcessarEventoAcesso(
                $dispositivo->dis_id,
                $objectChange['values'] ?? [],
                [
                    'ip' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]
            ));

            $disparados++;
        }

        return response()->json([
            'success' => true,
            'queued' => $disparados,
        ]);
    }

    public function receberEventoCatraca(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('catra_event', $request);
    }

    public function receberHeartbeat(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('device_is_alive', $request);
    }

    public function receberFotoAcesso(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('access_photo', $request);
    }

    public function receberEstadoPorta(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('door', $request);
    }

    public function receberModoOperacao(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('operation_mode', $request);
    }

    public function receberTemplate(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('template', $request);
    }

    public function receberImagemUsuario(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('user_image', $request);
    }

    public function receberCartao(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('card', $request);
    }

    public function receberPin(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('pin', $request);
    }

    public function receberSenha(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('password', $request);
    }

    public function receberSecbox(Request $request): JsonResponse
    {
        return $this->responderNotificacaoAuxiliar('secbox', $request);
    }

    private function responderNotificacaoAuxiliar(string $tipo, Request $request): JsonResponse
    {
        $this->eventoAcessoService->registrarNotificacaoAuxiliar($tipo, $this->dispositivo($request), $request->all());

        return response()->json([
            'success' => true,
            'type' => $tipo,
        ]);
    }

    private function dispositivo(Request $request): DispositivoAcesso
    {
        return $request->attributes->get('dispositivoAcesso')
            ?: $request->attributes->get('dispositivo_acesso');
    }
}
