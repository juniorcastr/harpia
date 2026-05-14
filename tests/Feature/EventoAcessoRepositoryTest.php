<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modulos\RH\Models\EventoAcesso;
use Modulos\RH\Repositories\EventoAcessoRepository;

class EventoAcessoRepositoryTest extends \TestCase
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

    public function testPaginacaoAdminAceitaFiltroDeDataNoFormatoBrasileiro(): void
    {
        $pessoaId = DB::table('gra_pessoas')->insertGetId([
            'pes_nome' => 'Carlos Monitor',
            'pes_email' => 'carlos@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $colaboradorId = DB::table('reh_colaboradores')->insertGetId([
            'col_pes_id' => $pessoaId,
            'col_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dispositivoId = DB::table('reh_dispositivos_acesso')->insertGetId([
            'dis_nome' => 'Portaria Principal',
            'dis_identificador' => 'idf-001',
            'dis_tipo' => 'entrada',
            'dis_ip' => '10.0.0.10',
            'dis_modelo' => 'iDFace',
            'dis_token_api' => str_repeat('c', 64),
            'dis_status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EventoAcesso::create([
            'eva_matricula' => (string) $colaboradorId,
            'eva_col_id' => $colaboradorId,
            'eva_dis_id' => $dispositivoId,
            'eva_tipo' => 'entrada',
            'eva_data_hora' => '2026-06-20 08:10:00',
            'eva_origem' => 'idface',
            'eva_status' => 'processado',
            'eva_status_mensagem' => null,
            'eva_hash' => hash('sha256', 'evento-acesso-2026-06-20 08:10:00'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repository = new EventoAcessoRepository(new EventoAcesso());
        $resultado = $repository->paginateAdmin([
            'data_inicio' => '20/06/2026',
            'data_fim' => '20/06/2026',
        ]);

        $this->assertSame(1, $resultado->total());
        $this->assertSame('Carlos Monitor', $resultado->first()->pes_nome);
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
            $table->string('col_status')->default('ativo');
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
