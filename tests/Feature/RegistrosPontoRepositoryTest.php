<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modulos\RH\Models\EventoAcesso;
use Modulos\RH\Repositories\RegistrosPontoRepository;

class RegistrosPontoRepositoryTest extends \TestCase
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

    public function testResumoAgrupaDiaECalculaTotalPorParesDeEntradaESaida(): void
    {
        $setorId = DB::table('reh_setores')->insertGetId([
            'set_descricao' => 'Portaria',
            'set_sigla' => 'PRT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pessoaId = DB::table('gra_pessoas')->insertGetId([
            'pes_nome' => 'Joao Silva',
            'pes_email' => 'joao@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $colaboradorId = DB::table('reh_colaboradores')->insertGetId([
            'col_pes_id' => $pessoaId,
            'col_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_colaboradores_funcoes')->insert([
            'cfn_set_id' => $setorId,
            'cfn_fun_id' => 1,
            'cfn_col_id' => $colaboradorId,
            'cfn_data_inicio' => '2026-01-01',
            'cfn_data_fim' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_dispositivos_acesso')->insert([
            'dis_id' => 1,
            'dis_nome' => 'Entrada Principal',
            'dis_identificador' => 'disp-1',
            'dis_tipo' => 'entrada',
            'dis_ip' => '127.0.0.1',
            'dis_modelo' => 'iDFace',
            'dis_token_api' => str_repeat('a', 64),
            'dis_status' => 'ativo',
            'dis_observacao' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['entrada', '2026-05-11 07:58:12'],
            ['saida', '2026-05-11 12:02:45'],
            ['entrada', '2026-05-11 13:15:30'],
            ['saida', '2026-05-11 17:05:18'],
        ] as [$tipo, $dataHora]) {
            EventoAcesso::create([
                'eva_matricula' => (string) $colaboradorId,
                'eva_col_id' => $colaboradorId,
                'eva_dis_id' => 1,
                'eva_tipo' => $tipo,
                'eva_data_hora' => $dataHora,
                'eva_origem' => 'idface',
                'eva_status' => 'processado',
                'eva_status_mensagem' => null,
                'eva_hash' => hash('sha256', $tipo . $dataHora),
            ]);
        }

        $repository = new RegistrosPontoRepository(new EventoAcesso());
        $resultado = $repository->buscarResumo([
            'data_inicio' => '2026-05-11',
            'data_fim' => '2026-05-11',
        ], false);

        $this->assertCount(1, $resultado);
        $this->assertSame('07:54', $resultado->first()->total_horas);
        $this->assertSame('idface', $resultado->first()->origem_label);
        $this->assertSame('processado', $resultado->first()->status_label);
    }

    public function testResumoAceitaFiltroDeDataNoFormatoBrasileiro(): void
    {
        $pessoaId = DB::table('gra_pessoas')->insertGetId([
            'pes_nome' => 'Maria Souza',
            'pes_email' => 'maria@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $colaboradorId = DB::table('reh_colaboradores')->insertGetId([
            'col_pes_id' => $pessoaId,
            'col_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_dispositivos_acesso')->insert([
            'dis_id' => 2,
            'dis_nome' => 'Saida Principal',
            'dis_identificador' => 'disp-2',
            'dis_tipo' => 'saida',
            'dis_ip' => '127.0.0.2',
            'dis_modelo' => 'iDFace',
            'dis_token_api' => str_repeat('b', 64),
            'dis_status' => 'ativo',
            'dis_observacao' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EventoAcesso::create([
            'eva_matricula' => (string) $colaboradorId,
            'eva_col_id' => $colaboradorId,
            'eva_dis_id' => 2,
            'eva_tipo' => 'entrada',
            'eva_data_hora' => '2026-06-20 08:00:00',
            'eva_origem' => 'idface',
            'eva_status' => 'processado',
            'eva_status_mensagem' => null,
            'eva_hash' => hash('sha256', 'entrada-2026-06-20 08:00:00'),
        ]);

        $repository = new RegistrosPontoRepository(new EventoAcesso());
        $resultado = $repository->buscarResumo([
            'data_inicio' => '20/06/2026',
            'data_fim' => '20/06/2026',
        ], false);

        $this->assertCount(1, $resultado);
        $this->assertSame('Maria Souza', $resultado->first()->pes_nome);
    }

    private function criarSchemaMinimo(): void
    {
        Schema::create('gra_pessoas', function (Blueprint $table) {
            $table->increments('pes_id');
            $table->string('pes_nome');
            $table->string('pes_email')->nullable();
            $table->timestamps();
        });

        Schema::create('reh_colaboradores', function (Blueprint $table) {
            $table->increments('col_id');
            $table->integer('col_pes_id')->unsigned();
            $table->string('col_status');
            $table->timestamps();
        });

        Schema::create('reh_setores', function (Blueprint $table) {
            $table->increments('set_id');
            $table->string('set_descricao');
            $table->string('set_sigla');
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

        Schema::create('reh_colaboradores_funcoes', function (Blueprint $table) {
            $table->increments('cfn_id');
            $table->integer('cfn_set_id')->unsigned()->nullable();
            $table->integer('cfn_fun_id')->unsigned()->nullable();
            $table->integer('cfn_col_id')->unsigned();
            $table->date('cfn_data_inicio')->nullable();
            $table->date('cfn_data_fim')->nullable();
            $table->timestamps();
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
