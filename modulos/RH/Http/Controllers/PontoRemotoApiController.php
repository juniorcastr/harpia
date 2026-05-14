<?php

namespace Modulos\RH\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\RegistroPontoRemotoRequest;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Services\PontoRemotoService;

class PontoRemotoApiController extends BaseController
{
    public function __construct(
        private ColaboradorRepository $colaboradorRepository,
        private PontoRemotoService $pontoRemotoService
    ) {
    }

    public function postEntrada(RegistroPontoRemotoRequest $request)
    {
        return $this->registrar($request, 'entrada');
    }

    public function postSaida(RegistroPontoRemotoRequest $request)
    {
        return $this->registrar($request, 'saida');
    }

    private function registrar(RegistroPontoRemotoRequest $request, string $tipo)
    {
        $colaborador = $this->colaboradorRepository->buscarAtivoPorEmail((string) $request->get('email'));

        if (!$colaborador || !$colaborador->pessoa) {
            return response()->json([
                'success' => false,
                'message' => 'Colaborador não encontrado.',
            ], 404);
        }

        if (!$this->validarNascimento($colaborador->pessoa->getRawOriginal('pes_nascimento'), (string) $request->get('data_nascimento'))) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos.',
            ], 401);
        }

        try {
            DB::beginTransaction();

            $evento = $this->pontoRemotoService->registrar($colaborador, $tipo, [
                'observacao' => $request->get('observacao'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'canal' => 'api',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro enviado para aprovação.',
                'eva_id' => $evento->eva_id,
            ]);
        } catch (\InvalidArgumentException $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Exception $exception) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $exception;
            }

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível registrar o ponto remoto.',
            ], 500);
        }
    }

    private function validarNascimento(?string $valorBanco, string $valorInformado): bool
    {
        if (!$valorBanco) {
            return false;
        }

        try {
            return Carbon::parse($valorBanco)->toDateString() === Carbon::parse($valorInformado)->toDateString();
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
