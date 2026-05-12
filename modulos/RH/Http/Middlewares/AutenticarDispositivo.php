<?php

namespace Modulos\RH\Http\Middlewares;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modulos\RH\Models\DispositivoAcesso;
use Symfony\Component\HttpFoundation\Response;

class AutenticarDispositivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $dispositivo = $this->resolverDispositivo($request);

        if (!$dispositivo) {
            return $this->jsonError('Dispositivo não autenticado.', Response::HTTP_UNAUTHORIZED);
        }

        if ($dispositivo->dis_status !== 'ativo') {
            return $this->jsonError('Dispositivo inativo.', Response::HTTP_FORBIDDEN);
        }

        if ($dispositivo->dis_ip && !$this->ipPermitido($request, $dispositivo)) {
            return $this->jsonError('IP do dispositivo não autorizado.', Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('dispositivoAcesso', $dispositivo);
        $request->attributes->set('dispositivo_acesso', $dispositivo);

        return $next($request);
    }

    private function resolverDispositivo(Request $request): ?DispositivoAcesso
    {
        $token = $request->bearerToken() ?: $request->header('X-Device-Token');

        if ($token) {
            return DispositivoAcesso::where('dis_token_api', $token)->first();
        }

        $deviceId = $request->input('device_id');

        if ($deviceId === null) {
            return null;
        }

        return DispositivoAcesso::where('dis_identificador', (string) $deviceId)->first();
    }

    private function ipPermitido(Request $request, DispositivoAcesso $dispositivo): bool
    {
        return $request->ip() === $dispositivo->dis_ip;
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'success' => false,
        ], $status);
    }
}
