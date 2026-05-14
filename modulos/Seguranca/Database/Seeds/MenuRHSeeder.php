<?php

namespace Modulos\Seguranca\Database\Seeds;

use Illuminate\Database\Seeder;

use Modulos\Seguranca\Models\MenuItem;
use Modulos\Seguranca\Models\Modulo;
use Modulos\Seguranca\Models\Perfil;
use Modulos\Seguranca\Models\Permissao;
use Modulos\Seguranca\Models\Usuario;

class MenuRHSeeder extends Seeder
{
    public function run()
    {

        $modulo= Modulo::where('mod_slug', 'rh')->first();

        // Criando itens no menu

        // Categoria Monitoramento
        $rh = MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Recursos Humanos',
            'mit_icone' => 'fa fa-file-text',
            'mit_ordem' => 1
        ]);

        // Item Dashboard
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Dashboard',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-tachometer',
            'mit_rota' => 'rh.index.index',
            'mit_ordem' => 1
        ]);

        // Categoria cadastros
        $rh = MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Cadastros',
            'mit_icone' => 'fa fa-plus',
            'mit_ordem' => 1
        ]);

        // Item areas conhecimentos
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Áreas de Conhecimento',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-tachometer',
            'mit_rota' => 'rh.areasconhecimentos.index',
            'mit_ordem' => 1
        ]);

        // Item bancos
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Bancos',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-bank',
            'mit_rota' => 'rh.bancos.index',
            'mit_ordem' => 2
        ]);

        // Item vinculos
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Vínculos',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-link',
            'mit_rota' => 'rh.vinculos.index',
            'mit_ordem' => 3
        ]);

        // Item funcoes
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Funções',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-user',
            'mit_rota' => 'rh.funcoes.index',
            'mit_ordem' => 4
        ]);

        // Item setores
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Setores',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-users',
            'mit_rota' => 'rh.setores.index',
            'mit_ordem' => 5
        ]);

        // Item períodos laborais
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Períodos Laborais',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-calendar',
            'mit_rota' => 'rh.periodoslaborais.index',
            'mit_ordem' => $modulo->mod_id
        ]);

        // Item colaboradores
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Colaboradores',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-user',
            'mit_rota' => 'rh.colaboradores.index',
            'mit_ordem' => $modulo->mod_id
        ]);

        // Item fontes pagadora
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Fontes Pagadoras',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-money',
            'mit_rota' => 'rh.fontespagadoras.index',
            'mit_ordem' => 8
        ]);

        // Item calendário
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Calendários',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-calendar',
            'mit_rota' => 'rh.calendarios.index',
            'mit_ordem' => 8
        ]);

        // Item calendário
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Horas Trabalhadas',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-calendar',
            'mit_rota' => 'rh.horastrabalhadas.index',
            'mit_ordem' => 8
        ]);

        // Categoria controle de acesso
        $rh = MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Controle de Acesso',
            'mit_icone' => 'fa fa-id-card',
            'mit_ordem' => 2
        ]);

        // Item registros de ponto
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Registros de Ponto',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-clock-o',
            'mit_rota' => 'rh.registros-ponto.index',
            'mit_ordem' => 1
        ]);

        // Item eventos de acesso
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Eventos de Acesso',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-exchange',
            'mit_rota' => 'rh.eventos-acesso.index',
            'mit_ordem' => 2
        ]);

        // Item dispositivos de acesso
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Dispositivos de Acesso',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-desktop',
            'mit_rota' => 'rh.dispositivos-acesso.index',
            'mit_ordem' => 3
        ]);

        // Item usuários do dispositivo
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Usuários do Dispositivo',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-users',
            'mit_rota' => 'rh.dispositivos-usuarios.index',
            'mit_ordem' => 4
        ]);

        // Item vincular colaboradores
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Vincular Colaboradores',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-link',
            'mit_rota' => 'rh.vincular-colaboradores.index',
            'mit_ordem' => 5
        ]);

        // Item teste de dispositivos
        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Teste de Dispositivos',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-stethoscope',
            'mit_rota' => 'rh.teste-dispositivo.index',
            'mit_ordem' => 6
        ]);

        // Categoria ponto remoto
        $rh = MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Ponto Remoto',
            'mit_icone' => 'fa fa-laptop',
            'mit_ordem' => 3
        ]);

        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Meus Registros',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-clock-o',
            'mit_rota' => 'rh.ponto-remoto.index',
            'mit_ordem' => 1
        ]);

        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Aprovações Pendentes',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-check-square-o',
            'mit_rota' => 'rh.aprovacoes-ponto.index',
            'mit_ordem' => 2
        ]);

        MenuItem::create([
            'mit_mod_id' => $modulo->mod_id,
            'mit_nome' => 'Configurações do Ponto',
            'mit_item_pai' => $rh->mit_id,
            'mit_icone' => 'fa fa-cogs',
            'mit_rota' => 'rh.configuracoes-ponto.index',
            'mit_ordem' => 3
        ]);
    }
}
