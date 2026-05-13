<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MonitorControllerTest extends \TestCase
{
    public function createApplication()
    {
        putenv('DB_CONNECTION=sqlite_testing');

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->criarSchemaMinimo();
    }

    public function testReceberLogAcessoCriaEventoProcessado(): void
    {
        $setorId = DB::table('reh_setores')->insertGetId([
            'set_descricao' => 'Portaria',
            'set_sigla' => 'PRT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $funcaoId = DB::table('reh_funcoes')->insertGetId([
            'fun_descricao' => 'Vigia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pessoaId = DB::table('gra_pessoas')->insertGetId([
            'pes_nome' => 'Maria da Silva',
            'pes_sexo' => 'F',
            'pes_email' => 'maria.silva@example.test',
            'pes_telefone' => '999999999',
            'pes_nascimento' => '1990-01-01',
            'pes_mae' => 'Fulana',
            'pes_pai' => 'Fulano',
            'pes_estado_civil' => 'solteiro',
            'pes_naturalidade' => 'Sao Luis',
            'pes_nacionalidade' => 'Brasileira',
            'pes_raca' => 'Parda',
            'pes_necessidade_especial' => null,
            'pes_estrangeiro' => 0,
            'pes_endereco' => 'Rua A',
            'pes_numero' => '1',
            'pes_complemento' => null,
            'pes_cep' => '65000000',
            'pes_cidade' => 'Sao Luis',
            'pes_bairro' => 'Centro',
            'pes_estado' => 'MA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $colaboradorId = DB::table('reh_colaboradores')->insertGetId([
            'col_pes_id' => $pessoaId,
            'col_set_id' => $setorId,
            'col_fun_id' => $funcaoId,
            'col_qtd_filho' => 0,
            'col_data_admissao' => '2024-01-01',
            'col_ch_diaria' => 8,
            'col_codigo_catraca' => null,
            'col_vinculo_universidade' => 1,
            'col_matricula_universidade' => '123',
            'col_observacao' => null,
            'col_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispositivoId = DB::table('reh_dispositivos_acesso')->insertGetId([
            'dis_nome' => 'iDFace Entrada',
            'dis_identificador' => '478435',
            'dis_tipo' => 'entrada',
            'dis_ip' => '127.0.0.1',
            'dis_modelo' => 'iDFace',
            'dis_token_api' => str_repeat('a', 64),
            'dis_status' => 'ativo',
            'dis_observacao' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_mapeamento_dispositivo')->insert([
            'map_dis_id' => $dispositivoId,
            'map_col_id' => $colaboradorId,
            'map_user_id' => '25',
            'map_registration' => (string) $colaboradorId,
            'map_nome_dispositivo' => 'Maria da Silva',
            'map_ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'device_id' => 478435,
            'object_changes' => [
                [
                    'object' => 'access_logs',
                    'type' => 'inserted',
                    'values' => [
                        'id' => '519',
                        'time' => (string) now()->timestamp,
                        'event' => '7',
                        'device_id' => '478435',
                        'identifier_id' => '0',
                        'user_id' => '25',
                        'portal_id' => '1',
                        'identification_rule_id' => '0',
                        'card_value' => '0',
                        'log_type_id' => '-1',
                    ],
                ],
            ],
        ];

        $response = $this->call(
            'POST',
            '/api/rh/monitor/notifications/dao',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
            json_encode($payload)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'queued' => 1,
        ]);

        $this->assertDatabaseHas('reh_eventos_acesso', [
            'eva_dis_id' => $dispositivoId,
            'eva_col_id' => $colaboradorId,
            'eva_tipo' => 'entrada',
            'eva_origem' => 'idface',
            'eva_status' => 'processado',
        ]);
    }

    private function criarSchemaMinimo(): void
    {
        Schema::create('gra_pessoas', function (Blueprint $table) {
            $table->increments('pes_id');
            $table->string('pes_nome', 150);
            $table->string('pes_email', 150)->unique();
            $table->string('pes_telefone', 20);
            $table->enum('pes_sexo', ['M', 'F']);
            $table->date('pes_nascimento');
            $table->string('pes_mae', 150);
            $table->string('pes_pai', 150)->nullable();
            $table->enum('pes_estado_civil', ['solteiro', 'casado', 'divorciado', 'viuvo(a)', 'uniao_estavel', 'outros'])->nullable();
            $table->string('pes_naturalidade', 45)->nullable();
            $table->string('pes_nacionalidade', 45)->nullable();
            $table->string('pes_raca', 45)->nullable();
            $table->string('pes_necessidade_especial', 150)->nullable();
            $table->boolean('pes_estrangeiro')->default(0);
            $table->string('pes_endereco');
            $table->string('pes_numero', 45);
            $table->string('pes_complemento', 150)->nullable();
            $table->string('pes_cep', 10);
            $table->string('pes_cidade', 150);
            $table->string('pes_bairro', 150);
            $table->char('pes_estado', 2);
            $table->timestamps();
        });

        Schema::create('reh_setores', function (Blueprint $table) {
            $table->increments('set_id');
            $table->string('set_descricao', 60);
            $table->string('set_sigla', 15);
            $table->timestamps();
        });

        Schema::create('reh_funcoes', function (Blueprint $table) {
            $table->increments('fun_id');
            $table->string('fun_descricao', 60);
            $table->timestamps();
        });

        Schema::create('reh_colaboradores', function (Blueprint $table) {
            $table->increments('col_id');
            $table->integer('col_pes_id')->unsigned();
            $table->integer('col_set_id')->unsigned();
            $table->integer('col_fun_id')->unsigned();
            $table->integer('col_qtd_filho')->nullable();
            $table->date('col_data_admissao');
            $table->integer('col_ch_diaria');
            $table->string('col_codigo_catraca', 150)->nullable();
            $table->boolean('col_vinculo_universidade');
            $table->string('col_matricula_universidade', 150)->nullable();
            $table->longText('col_observacao')->nullable();
            $table->enum('col_status', ['ativo', 'afastado', 'desligado']);
            $table->timestamps();
        });

        Schema::create('reh_dispositivos_acesso', function (Blueprint $table) {
            $table->increments('dis_id');
            $table->string('dis_nome');
            $table->string('dis_identificador')->unique();
            $table->enum('dis_tipo', ['entrada', 'saida']);
            $table->string('dis_ip', 45)->nullable();
            $table->string('dis_modelo')->nullable();
            $table->string('dis_token_api', 64)->unique();
            $table->enum('dis_status', ['ativo', 'inativo'])->default('ativo');
            $table->text('dis_observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('reh_mapeamento_dispositivo', function (Blueprint $table) {
            $table->increments('map_id');
            $table->integer('map_dis_id')->unsigned();
            $table->integer('map_col_id')->unsigned()->nullable();
            $table->string('map_user_id', 20);
            $table->string('map_registration', 50);
            $table->string('map_nome_dispositivo')->nullable();
            $table->boolean('map_ativo')->default(true);
            $table->timestamps();
            $table->unique(['map_dis_id', 'map_user_id']);
        });

        Schema::create('reh_eventos_acesso', function (Blueprint $table) {
            $table->increments('eva_id');
            $table->string('eva_matricula', 50);
            $table->integer('eva_col_id')->unsigned()->nullable();
            $table->integer('eva_dis_id')->unsigned()->nullable();
            $table->enum('eva_tipo', ['entrada', 'saida']);
            $table->dateTime('eva_data_hora');
            $table->enum('eva_origem', ['idface', 'home_office']);
            $table->enum('eva_status', ['bruto', 'processado', 'duplicado', 'erro', 'pendente', 'aprovado', 'reprovado'])->default('bruto');
            $table->string('eva_status_mensagem')->nullable();
            $table->string('eva_hash', 64)->unique();
            $table->string('eva_ip_origem', 45)->nullable();
            $table->text('eva_user_agent')->nullable();
            $table->text('eva_observacao')->nullable();
            $table->timestamps();
        });
    }
}
