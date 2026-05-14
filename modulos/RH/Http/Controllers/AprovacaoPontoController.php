<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\AprovacaoPontoRequest;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\EventoAcessoRepository;
use Modulos\RH\Services\AprovacaoPontoService;
use Modulos\RH\Services\AprovadorPontoService;

class AprovacaoPontoController extends BaseController
{
    public function __construct(
        private ColaboradorRepository $colaboradorRepository,
        private EventoAcessoRepository $eventoRepository,
        private AprovadorPontoService $aprovadorPontoService,
        private AprovacaoPontoService $aprovacaoPontoService
    ) {
    }

    public function getIndex()
    {
        $aprovador = $this->resolverColaboradorAutenticado();

        if (!$aprovador) {
            flash()->error('Nenhum colaborador ativo vinculado ao usuário autenticado.');
            return redirect()->route('rh.index.index');
        }

        $colaboradorIds = $this->aprovadorPontoService->listarColaboradorIdsAprovaveis($aprovador);
        $pendencias = $this->eventoRepository->listarPendenciasParaColaboradores($colaboradorIds);

        return view('RH::aprovacoes_ponto.index', compact('aprovador', 'pendencias'));
    }

    public function getShow($eventoId)
    {
        $aprovador = $this->resolverColaboradorAutenticado();
        $evento = $this->eventoRepository->buscarPendenteHomeOffice((int) $eventoId);

        if (!$aprovador || !$evento || !$evento->colaborador) {
            flash()->error('Registro pendente não encontrado.');
            return redirect()->route('rh.aprovacoes-ponto.index');
        }

        if (!$this->aprovadorPontoService->podeAprovar($aprovador, $evento->colaborador)) {
            flash()->error('Você não possui permissão para visualizar esta pendência.');
            return redirect()->route('rh.aprovacoes-ponto.index');
        }

        return view('RH::aprovacoes_ponto.show', compact('aprovador', 'evento'));
    }

    public function postAprovar($eventoId, AprovacaoPontoRequest $request)
    {
        return $this->processar($eventoId, $request, 'aprovar');
    }

    public function postReprovar($eventoId, AprovacaoPontoRequest $request)
    {
        return $this->processar($eventoId, $request, 'reprovar');
    }

    private function processar($eventoId, AprovacaoPontoRequest $request, string $acao)
    {
        $aprovador = $this->resolverColaboradorAutenticado();
        $evento = $this->eventoRepository->buscarPendenteHomeOffice((int) $eventoId);

        if (!$aprovador || !$evento) {
            flash()->error('Registro pendente não encontrado.');
            return redirect()->route('rh.aprovacoes-ponto.index');
        }

        try {
            DB::beginTransaction();

            if ($acao === 'aprovar') {
                $this->aprovacaoPontoService->aprovar($evento, $aprovador);
                $mensagem = 'Registro aprovado com sucesso.';
            } else {
                $this->aprovacaoPontoService->reprovar($evento, $aprovador, (string) $request->get('motivo'));
                $mensagem = 'Registro reprovado com sucesso.';
            }

            DB::commit();
            flash()->success($mensagem);
        } catch (\InvalidArgumentException $exception) {
            DB::rollBack();
            flash()->error($exception->getMessage());
        } catch (\Exception $exception) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $exception;
            }

            flash()->error('Não foi possível processar a aprovação do registro remoto.');
        }

        return redirect()->route('rh.aprovacoes-ponto.index');
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
