<?php

namespace Modulos\RH\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\RH\Http\Requests\ConfiguracaoPontoRequest;
use Modulos\RH\Repositories\ConfiguracaoPontoRepository;

class ConfiguracaoPontoController extends BaseController
{
    public function __construct(
        private ConfiguracaoPontoRepository $configuracaoRepository
    ) {
    }

    public function getIndex()
    {
        $configuracoes = $this->configuracaoRepository->obterMapa();

        return view('RH::configuracao_ponto.index', compact('configuracoes'));
    }

    public function putEdit(ConfiguracaoPontoRequest $request)
    {
        try {
            DB::beginTransaction();

            $this->configuracaoRepository->salvarMapa([
                'ponto.janela_inicio' => $request->get('janela_inicio'),
                'ponto.janela_fim' => $request->get('janela_fim'),
                'ponto.anti_duplicidade_seg' => $request->get('anti_duplicidade_seg'),
                'ponto.retry_max_tentativas' => $request->get('retry_max_tentativas'),
                'ponto.retry_delay_min' => $request->get('retry_delay_min'),
            ]);

            DB::commit();

            flash()->success('Configurações do ponto atualizadas com sucesso.');
        } catch (\Exception $exception) {
            DB::rollBack();

            if (config('app.debug')) {
                throw $exception;
            }

            flash()->error('Não foi possível atualizar as configurações do ponto.');
        }

        return redirect()->route('rh.configuracoes-ponto.index');
    }
}
