<?php

namespace Modulos\RH\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modulos\RH\Models\AprovacaoPonto;
use Modulos\RH\Models\Colaborador;
use Modulos\RH\Models\EventoAcesso;

class AprovacaoPontoService
{
    public function __construct(
        private AprovadorPontoService $aprovadorPontoService
    ) {
    }

    public function aprovar(EventoAcesso $evento, Colaborador $aprovador): AprovacaoPonto
    {
        $this->validarEventoPendente($evento);
        $this->validarPermissao($evento, $aprovador);

        $evento->fill([
            'eva_status' => 'aprovado',
            'eva_status_mensagem' => 'Registro remoto aprovado pelo gestor.',
        ])->save();

        $aprovacao = AprovacaoPonto::create([
            'app_eva_id' => $evento->eva_id,
            'app_aprovador_col_id' => $aprovador->col_id,
            'app_data_aprovacao' => Carbon::now()->toDateTimeString(),
            'app_status' => 'aprovado',
            'app_motivo' => null,
            'app_hora_ajustada' => null,
        ]);

        Log::channel('ponto')->info('Registro remoto aprovado.', [
            'evento_id' => $evento->eva_id,
            'colaborador_id' => $evento->eva_col_id,
            'aprovador_id' => $aprovador->col_id,
        ]);

        return $aprovacao;
    }

    public function reprovar(EventoAcesso $evento, Colaborador $aprovador, string $motivo): AprovacaoPonto
    {
        $this->validarEventoPendente($evento);
        $this->validarPermissao($evento, $aprovador);

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new \InvalidArgumentException('Informe o motivo da reprovação.');
        }

        $evento->fill([
            'eva_status' => 'reprovado',
            'eva_status_mensagem' => 'Registro remoto reprovado pelo gestor.',
        ])->save();

        $aprovacao = AprovacaoPonto::create([
            'app_eva_id' => $evento->eva_id,
            'app_aprovador_col_id' => $aprovador->col_id,
            'app_data_aprovacao' => Carbon::now()->toDateTimeString(),
            'app_status' => 'reprovado',
            'app_motivo' => $motivo,
            'app_hora_ajustada' => null,
        ]);

        Log::channel('ponto')->info('Registro remoto reprovado.', [
            'evento_id' => $evento->eva_id,
            'colaborador_id' => $evento->eva_col_id,
            'aprovador_id' => $aprovador->col_id,
            'motivo' => $motivo,
        ]);

        return $aprovacao;
    }

    private function validarEventoPendente(EventoAcesso $evento): void
    {
        if ($evento->eva_origem !== 'home_office') {
            throw new \InvalidArgumentException('A aprovação é permitida apenas para registros remotos.');
        }

        if ($evento->eva_status !== 'pendente') {
            throw new \InvalidArgumentException('O registro informado não está mais pendente de aprovação.');
        }

        if (!$evento->colaborador) {
            throw new \InvalidArgumentException('O registro remoto não possui colaborador vinculado.');
        }
    }

    private function validarPermissao(EventoAcesso $evento, Colaborador $aprovador): void
    {
        if (!$this->aprovadorPontoService->podeAprovar($aprovador, $evento->colaborador)) {
            throw new \InvalidArgumentException('Você não possui permissão para aprovar este colaborador.');
        }
    }
}
