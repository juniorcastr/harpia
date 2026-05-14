<?php

namespace Modulos\RH\Repositories;

use Modulos\Core\Repository\BaseRepository;
use Modulos\RH\Models\ConfiguracaoPonto;

class ConfiguracaoPontoRepository extends BaseRepository
{
    public const CHAVES_PADRAO = [
        'ponto.janela_inicio' => 'Horário inicial da janela válida para registros de ponto.',
        'ponto.janela_fim' => 'Horário final da janela válida para registros de ponto.',
        'ponto.anti_duplicidade_seg' => 'Intervalo mínimo em segundos para descartar eventos duplicados.',
        'ponto.retry_max_tentativas' => 'Quantidade máxima de tentativas de reprocessamento.',
        'ponto.retry_delay_min' => 'Atraso em minutos entre tentativas de reprocessamento.',
    ];

    public function __construct(ConfiguracaoPonto $configuracaoPonto)
    {
        $this->model = $configuracaoPonto;
    }

    public function obterMapa(array $chaves = []): array
    {
        $chaves = empty($chaves) ? array_keys(self::CHAVES_PADRAO) : $chaves;

        $valores = $this->model->newQuery()
            ->whereIn('cfg_chave', $chaves)
            ->pluck('cfg_valor', 'cfg_chave')
            ->toArray();

        $resultado = [];

        foreach ($chaves as $chave) {
            $resultado[$chave] = $valores[$chave] ?? null;
        }

        return $resultado;
    }

    public function obterValor(string $chave, mixed $padrao = null): mixed
    {
        return $this->model->newQuery()
            ->where('cfg_chave', $chave)
            ->value('cfg_valor') ?? $padrao;
    }

    public function salvarMapa(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            $this->model->newQuery()->updateOrCreate(
                ['cfg_chave' => $chave],
                [
                    'cfg_valor' => (string) $valor,
                    'cfg_descricao' => self::CHAVES_PADRAO[$chave] ?? null,
                ]
            );
        }
    }
}
