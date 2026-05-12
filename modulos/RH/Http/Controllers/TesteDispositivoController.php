<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Services\ControlIdApiClient;
use Symfony\Component\HttpFoundation\Response;

class TesteDispositivoController extends BaseController
{
    public function __construct(
        private DispositivoAcessoRepository $dispositivoRepository,
        private ControlIdApiClient $apiClient
    ) {
    }

    public function getIndex(): View
    {
        $dispositivos = $this->dispositivoRepository->lists('dis_id', 'dis_nome');

        return view('RH::teste_dispositivo.index', compact('dispositivos'));
    }

    public function postPing(Request $request): JsonResponse
    {
        return $this->executarTeste($request, function (DispositivoAcesso $dispositivo): array {
            return $this->apiClient->ping($dispositivo);
        });
    }

    public function postListarUsuarios(Request $request): JsonResponse
    {
        return $this->executarTeste($request, function (DispositivoAcesso $dispositivo): array {
            return $this->apiClient->loadObjects($dispositivo, 'users');
        });
    }

    public function postConsultarLogs(Request $request): JsonResponse
    {
        return $this->executarTeste($request, function (DispositivoAcesso $dispositivo): array {
            return $this->apiClient->loadObjects($dispositivo, 'access_logs');
        });
    }

    public function postStatusDispositivo(Request $request): JsonResponse
    {
        return $this->executarTeste($request, function (DispositivoAcesso $dispositivo): array {
            return [
                'dispositivo' => [
                    'dis_id' => $dispositivo->dis_id,
                    'dis_nome' => $dispositivo->dis_nome,
                    'dis_identificador' => $dispositivo->dis_identificador,
                    'dis_tipo' => $dispositivo->dis_tipo,
                    'dis_ip' => $dispositivo->dis_ip,
                    'dis_modelo' => $dispositivo->dis_modelo,
                    'dis_status' => $dispositivo->dis_status,
                ],
                'configuracao' => $this->apiClient->getConfiguration($dispositivo),
                'sistema' => $this->apiClient->getSystemInfo($dispositivo),
            ];
        });
    }

    private function executarTeste(Request $request, callable $callback): JsonResponse
    {
        $this->validate($request, [
            'dis_id' => 'required|integer',
        ]);

        $dispositivo = $this->dispositivoRepository->find($request->get('dis_id'));

        if (!$dispositivo) {
            return response()->json([
                'message' => 'Dispositivo não encontrado.',
                'success' => false,
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $callback($dispositivo),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $exception->getMessage()
                    : 'Não foi possível comunicar com o dispositivo informado.',
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
