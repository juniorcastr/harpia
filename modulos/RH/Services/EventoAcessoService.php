<?php

namespace Modulos\RH\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Models\EventoAcesso;

class EventoAcessoService
{
    private const EVENTOS_SUPORTADOS = [0, 1, 2, 3, 4, 5, 6, 7, 12];

    public function __construct(
        private AntiDuplicidadeService $antiDuplicidadeService,
        private MatchingService $matchingService
    ) {
    }

    public function processarEventoMonitor(DispositivoAcesso $dispositivo, array $evento, array $contexto = []): array
    {
        $userId = (string) ($evento['user_id'] ?? '');
        $deviceId = (string) ($evento['device_id'] ?? $dispositivo->dis_identificador);
        $timestamp = $this->resolverDataHora($evento['time'] ?? null);
        $eventCode = (int) ($evento['event'] ?? -1);

        if (!in_array($eventCode, self::EVENTOS_SUPORTADOS, true)) {
            return [
                'status' => 'ignorado',
                'motivo' => 'Código de evento não suportado para registro de acesso.',
            ];
        }

        $hash = $this->antiDuplicidadeService->gerarHash(
            $userId !== '' ? $userId : (string) ($evento['id'] ?? 'desconhecido'),
            $deviceId,
            $timestamp->toDateTimeString()
        );

        if ($this->antiDuplicidadeService->existeHash($hash)) {
            return [
                'status' => 'duplicado',
                'hash' => $hash,
            ];
        }

        $colaborador = $userId !== ''
            ? $this->matchingService->resolver($userId, $dispositivo->dis_id)
            : null;
        $matricula = $userId !== ''
            ? ($this->matchingService->resolverMatricula($userId, $dispositivo->dis_id) ?: $userId)
            : 'nao-identificado';

        $status = 'processado';
        $mensagem = $this->descricaoEvento($eventCode);

        if (!$colaborador) {
            $status = 'erro';
            $mensagem = 'Usuário não vinculado no Harpia.';
        } elseif ($eventCode === 12) {
            $status = 'erro';
            $mensagem = 'Acesso não autorizado pelo dispositivo.';
        } elseif ($dispositivo->dis_tipo === 'entrada' && $this->possuiEntradaAbertaNoDia($colaborador->col_id, $timestamp)) {
            $mensagem = 'Entrada registrada com outra entrada aberta no mesmo dia.';
        }

        $registro = EventoAcesso::create([
            'eva_matricula' => $matricula,
            'eva_col_id' => $colaborador?->col_id,
            'eva_dis_id' => $dispositivo->dis_id,
            'eva_tipo' => $dispositivo->dis_tipo,
            'eva_data_hora' => $timestamp->toDateTimeString(),
            'eva_origem' => 'idface',
            'eva_status' => $status,
            'eva_status_mensagem' => $mensagem,
            'eva_hash' => $hash,
            'eva_ip_origem' => $contexto['ip'] ?? null,
            'eva_user_agent' => $contexto['user_agent'] ?? null,
            'eva_observacao' => $this->montarObservacao($evento, $eventCode),
        ]);

        return [
            'status' => $status,
            'registro' => $registro,
            'hash' => $hash,
        ];
    }

    public function processarLoteAccessLogs(DispositivoAcesso $dispositivo, array $logs, array $contexto = []): array
    {
        $resultado = [
            'processado' => 0,
            'erro' => 0,
            'duplicado' => 0,
            'ignorado' => 0,
        ];

        foreach ($logs as $log) {
            $processamento = $this->processarEventoMonitor($dispositivo, $log, $contexto);
            $status = $processamento['status'];

            if (!array_key_exists($status, $resultado)) {
                $status = 'ignorado';
            }

            $resultado[$status]++;
        }

        return $resultado;
    }

    public function registrarNotificacaoAuxiliar(string $tipo, DispositivoAcesso $dispositivo, array $payload): void
    {
        Log::info('Notificação auxiliar recebida do Control iD', [
            'tipo' => $tipo,
            'dispositivo' => $dispositivo->dis_id,
            'payload' => $payload,
        ]);
    }

    private function resolverDataHora(mixed $valor): Carbon
    {
        if (is_numeric($valor)) {
            return Carbon::createFromTimestamp((int) $valor);
        }

        return Carbon::parse((string) $valor);
    }

    private function descricaoEvento(int $eventCode): string
    {
        return match ($eventCode) {
            0 => 'Identificação por senha.',
            1 => 'Identificação por cartão.',
            2 => 'Identificação por biometria.',
            3 => 'Identificação por cartão e senha.',
            4 => 'Identificação por cartão e biometria.',
            5 => 'Identificação por biometria e senha.',
            6 => 'Identificação por cartão, biometria e senha.',
            7 => 'Reconhecimento facial.',
            12 => 'Acesso negado.',
            default => 'Evento de acesso recebido.',
        };
    }

    private function montarObservacao(array $evento, int $eventCode): string
    {
        $observacao = [
            'event_code' => $eventCode,
            'event_description' => $this->descricaoEvento($eventCode),
        ];

        foreach (['id', 'identifier_id', 'portal_id', 'identification_rule_id', 'card_value', 'log_type_id'] as $campo) {
            if (array_key_exists($campo, $evento)) {
                $observacao[$campo] = $evento[$campo];
            }
        }

        return json_encode($observacao, JSON_UNESCAPED_UNICODE);
    }

    private function possuiEntradaAbertaNoDia(int $colaboradorId, Carbon $dataHora): bool
    {
        $inicioDoDia = $dataHora->copy()->startOfDay()->toDateTimeString();
        $fimDoDia = $dataHora->copy()->endOfDay()->toDateTimeString();

        $ultimaEntrada = EventoAcesso::where('eva_col_id', $colaboradorId)
            ->where('eva_tipo', 'entrada')
            ->where('eva_status', 'processado')
            ->whereBetween('eva_data_hora', [$inicioDoDia, $fimDoDia])
            ->orderByDesc('eva_data_hora')
            ->first();

        if (!$ultimaEntrada) {
            return false;
        }

        $ultimaSaida = EventoAcesso::where('eva_col_id', $colaboradorId)
            ->where('eva_tipo', 'saida')
            ->where('eva_status', 'processado')
            ->whereBetween('eva_data_hora', [$inicioDoDia, $fimDoDia])
            ->orderByDesc('eva_data_hora')
            ->first();

        if (!$ultimaSaida) {
            return true;
        }

        return $ultimaEntrada->eva_data_hora > $ultimaSaida->eva_data_hora;
    }
}
