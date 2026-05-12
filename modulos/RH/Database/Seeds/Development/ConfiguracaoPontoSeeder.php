<?php

namespace Modulos\RH\Database\Seeds\Development;

use Illuminate\Database\Seeder;
use Modulos\RH\Models\ConfiguracaoPonto;

class ConfiguracaoPontoSeeder extends Seeder
{
    public function run(): void
    {
        $configuracoes = [
            [
                'cfg_chave' => 'ponto.janela_inicio',
                'cfg_valor' => '05:00',
                'cfg_descricao' => 'Horário inicial da janela válida para registros de ponto.',
            ],
            [
                'cfg_chave' => 'ponto.janela_fim',
                'cfg_valor' => '23:00',
                'cfg_descricao' => 'Horário final da janela válida para registros de ponto.',
            ],
            [
                'cfg_chave' => 'ponto.anti_duplicidade_seg',
                'cfg_valor' => '60',
                'cfg_descricao' => 'Intervalo mínimo em segundos para descartar eventos duplicados.',
            ],
            [
                'cfg_chave' => 'ponto.retry_max_tentativas',
                'cfg_valor' => '3',
                'cfg_descricao' => 'Quantidade máxima de tentativas de reprocessamento.',
            ],
            [
                'cfg_chave' => 'ponto.retry_delay_min',
                'cfg_valor' => '5',
                'cfg_descricao' => 'Atraso em minutos entre tentativas de reprocessamento.',
            ],
        ];

        foreach ($configuracoes as $configuracao) {
            ConfiguracaoPonto::updateOrCreate(
                ['cfg_chave' => $configuracao['cfg_chave']],
                $configuracao
            );
        }
    }
}
