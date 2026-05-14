<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\RegistroPontoRemotoRequest;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Services\PontoRemotoService;

class PontoRemotoController extends BaseController
{
    public function __construct(
        private ColaboradorRepository $colaboradorRepository,
        private PontoRemotoService $pontoRemotoService
    ) {
    }

    public function getIndex()
    {
        $colaborador = $this->resolverColaboradorAutenticado();

        if (!$colaborador) {
            flash()->error('Nenhum colaborador ativo vinculado ao usuário autenticado.');
            return redirect()->route('rh.index.index');
        }

        $estadoAtual = $this->pontoRemotoService->obterEstadoAtualDoDia($colaborador);
        $registros = $this->pontoRemotoService->listarMeusRegistros($colaborador->col_id);

        return view('RH::ponto_remoto.index', compact('colaborador', 'estadoAtual', 'registros'));
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
        $colaborador = $this->resolverColaboradorAutenticado();

        if (!$colaborador) {
            flash()->error('Nenhum colaborador ativo vinculado ao usuário autenticado.');
            return redirect()->route('rh.index.index');
        }

        try {
            DB::beginTransaction();

            $evento = $this->pontoRemotoService->registrar($colaborador, $tipo, [
                'observacao' => $request->get('observacao'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'canal' => 'web',
            ]);

            DB::commit();

            flash()->success(sprintf(
                'Registro de %s enviado para aprovação do gestor.',
                $tipo
            ));

            return redirect()->route('rh.ponto-remoto.index', ['eva_id' => $evento->eva_id]);
        } catch (\InvalidArgumentException $exception) {
            DB::rollBack();
            flash()->error($exception->getMessage());
        } catch (\Exception $exception) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $exception;
            }

            flash()->error('Não foi possível registrar o ponto remoto.');
        }

        return redirect()->back()->withInput($request->all());
    }

    private function resolverColaboradorAutenticado()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->usr_pes_id) {
            return null;
        }

        return $this->colaboradorRepository->buscarAtivoPorPessoaId((int) $usuario->usr_pes_id);
    }
}
