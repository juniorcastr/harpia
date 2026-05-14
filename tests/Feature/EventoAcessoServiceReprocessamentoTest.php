<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modulos\RH\Models\EventoAcesso;
use Modulos\RH\Services\AntiDuplicidadeService;
use Modulos\RH\Services\EventoAcessoService;
use Modulos\RH\Services\MatchingService;

class EventoAcessoServiceReprocessamentoTest extends \TestCase
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

    public function testReprocessaEventoErroQuandoVinculoJaExiste(): void
    {
        DB::table('gra_pessoas')->insert([
            'pes_id' => 1,
            'pes_nome' => 'Joao Silva',
            'pes_email' => 'joao@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_colaboradores')->insert([
            'col_id' => 10,
            'col_pes_id' => 1,
            'col_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reh_mapeamento_dispositivo')->insert([
            'map_dis_id' => 1,
            'map_col_id' => 10,
            'map_user_id' => '25',
            'map_registration' => '10',
            'map_nome_dispositivo' => 'Joao',
            'map_ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $evento = EventoAcesso::create([
            'eva_matricula' => '25',
            'eva_col_id' => null,
            'eva_dis_id' => 1,
            'eva_tipo' => 'entrada',
            'eva_data_hora' => '2026-05-11 08:00:00',
            'eva_origem' => 'idface',
            'eva_status' => 'erro',
            'eva_status_mensagem' => 'Usuário não vinculado no Harpia.',
            'eva_hash' => hash('sha256', 'erro'),
            'eva_observacao' => json_encode([
                'event_code' => 7,
                'user_id' => '25',
            ]),
        ]);

        $service = new EventoAcessoService(
            new AntiDuplicidadeService(),
            new MatchingService()
        );

        $resultado = $service->reprocessarEventoComFalha($evento);

        $eventoAtualizado = EventoAcesso::find($evento->eva_id);

        $this->assertTrue($resultado);
        $this->assertSame('processado', $eventoAtualizado->eva_status);
        $this->assertSame(10, $eventoAtualizado->eva_col_id);
        $this->assertSame('10', $eventoAtualizado->eva_matricula);
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
            $table->integer('col_pes_id')->unsigned()->nullable();
            $table->string('col_status');
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
