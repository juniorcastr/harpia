<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Http\Request;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\DispositivoAcessoRepository;
use Modulos\RH\Repositories\EventoAcessoRepository;
use Modulos\RH\Services\EventoAcessoService;

class EventoAcessoController extends BaseController
{
    public function __construct(
        private EventoAcessoRepository $eventoRepository,
        private ColaboradorRepository $colaboradorRepository,
        private DispositivoAcessoRepository $dispositivoRepository,
        private EventoAcessoService $eventoAcessoService
    ) {
    }

    public function getIndex(Request $request)
    {
        $eventos = $this->eventoRepository->paginateAdmin($request->all());
        $colaboradores = $this->colaboradorRepository->listarAtivosParaSelecao();
        $dispositivos = $this->dispositivoRepository->lists('dis_id', 'dis_nome');
        $statusList = ['bruto', 'processado', 'duplicado', 'erro', 'pendente', 'aprovado', 'reprovado'];
        $origens = ['idface', 'home_office'];
        $tipos = ['entrada', 'saida'];

        return view('RH::eventos_acesso.index', compact('eventos', 'colaboradores', 'dispositivos', 'statusList', 'origens', 'tipos'));
    }

    public function getShow($eventoId)
    {
        $evento = $this->eventoRepository->buscarDetalhado((int) $eventoId);

        if (!$evento) {
            flash()->error('Evento de acesso não encontrado.');
            return redirect()->route('rh.eventos-acesso.index');
        }

        return view('RH::eventos_acesso.show', compact('evento'));
    }

    public function postReprocessar($eventoId)
    {
        $evento = $this->eventoRepository->buscarDetalhado((int) $eventoId);

        if (!$evento) {
            flash()->error('Evento de acesso não encontrado.');
            return redirect()->back();
        }

        try {
            $reprocessado = $this->eventoAcessoService->reprocessarEventoComFalha($evento);

            if ($reprocessado) {
                flash()->success('Evento reprocessado com sucesso.');
            } else {
                flash()->error('O evento não pôde ser reprocessado com o estado atual.');
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            flash()->error('Erro ao tentar reprocessar o evento.');
        }

        return redirect()->route('rh.eventos-acesso.show', ['id' => $eventoId]);
    }
}
