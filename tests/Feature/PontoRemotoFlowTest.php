<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modulos\RH\Models\EventoAcesso;
use Modulos\Seguranca\Models\Usuario;

class PontoRemotoFlowTest extends \TestCase
{
    public function createApplication()
    {
        putenv('DB_CONNECTION=sqlite_testing');
        putenv('IS_SECURITY_ENNABLED=0');

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->criarSchemaMinimo();
        $this->seedConfiguracoes();
    }

    public function testFluxoCompletoDeRegistroRemotoEAprovacaoPeloGestorDoSetor(): void
    {
        $setorId = $this->criarSetor('Tecnologia');
        [$gestorPessoaId, $gestorColaboradorId] = $this->criarColaborador('Gestor TI', 'gestor@example.test', '1980-01-10');
        $this->criarUsuario($gestorPessoaId, 'gestor@example.test');

        [$colaboradorPessoaId, $colaboradorId] = $this->criarColaborador('Analista Remoto', 'analista@example.test', '1992-03-20');
        $this->vincularSetorAtual($colaboradorId, $setorId);
        $this->vincularGestorAoSetor($gestorColaboradorId, $setorId);

        $response = $this->call(
            'POST',
            '/api/rh/ponto-remoto/entrada',
            [
                'email' => 'analista@example.test',
                'data_nascimento' => '1992-03-20',
                'observacao' => 'Atividade remota de suporte.',
            ],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Registro enviado para aprovação.',
        ]);

        $evento = EventoAcesso::query()->where('eva_col_id', $colaboradorId)->first();

        $this->assertNotNull($evento);
        $this->assertSame('pendente', $evento->eva_status);
        $this->assertSame('home_office', $evento->eva_origem);
        $this->assertSame('entrada', $evento->eva_tipo);

        $gestor = Usuario::query()->where('usr_pes_id', $gestorPessoaId)->first();
        $this->be($gestor);

        $approveResponse = $this->call('POST', '/rh/aprovacoes-ponto/aprovar/' . $evento->eva_id);

        $approveResponse->assertRedirect('/rh/aprovacoes-ponto');

        $this->assertDatabaseHas('reh_eventos_acesso', [
            'eva_id' => $evento->eva_id,
            'eva_status' => 'aprovado',
        ]);

        $this->assertDatabaseHas('reh_aprovacoes_ponto', [
            'app_eva_id' => $evento->eva_id,
            'app_aprovador_col_id' => $gestorColaboradorId,
            'app_status' => 'aprovado',
        ]);
    }

    public function testRegistroRemotoFalhaQuandoNaoExisteAprovadorResolvido(): void
    {
        [$pessoaId, $colaboradorId] = $this->criarColaborador('Colaborador Sem Gestor', 'sem-gestor@example.test', '1995-06-15');

        $response = $this->call(
            'POST',
            '/api/rh/ponto-remoto/entrada',
            [
                'email' => 'sem-gestor@example.test',
                'data_nascimento' => '1995-06-15',
            ],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Não foi encontrado gestor responsável para aprovar este registro remoto.',
        ]);

        $this->assertDatabaseMissing('reh_eventos_acesso', [
            'eva_col_id' => $colaboradorId,
            'eva_origem' => 'home_office',
        ]);
    }

    public function testGestorNaoAutorizadoNaoPodeAprovarPendenciaDeOutroColaborador(): void
    {
        $setorId = $this->criarSetor('Operações');

        [$gestorAutorizadoPessoaId, $gestorAutorizadoColaboradorId] = $this->criarColaborador(
            'Gestor Responsável',
            'gestor-responsavel@example.test',
            '1981-04-11'
        );
        $this->criarUsuario($gestorAutorizadoPessoaId, 'gestor-responsavel@example.test');

        [$gestorNaoAutorizadoPessoaId] = $this->criarColaborador(
            'Gestor Sem Permissão',
            'gestor-sem-permissao@example.test',
            '1984-09-21'
        );
        $this->criarUsuario($gestorNaoAutorizadoPessoaId, 'gestor-sem-permissao@example.test');

        [$colaboradorPessoaId, $colaboradorId] = $this->criarColaborador(
            'Colaborador Remoto',
            'colaborador-remoto@example.test',
            '1993-07-05'
        );
        $this->vincularSetorAtual($colaboradorId, $setorId);
        $this->vincularGestorAoSetor($gestorAutorizadoColaboradorId, $setorId);

        $registroResponse = $this->call(
            'POST',
            '/api/rh/ponto-remoto/entrada',
            [
                'email' => 'colaborador-remoto@example.test',
                'data_nascimento' => '1993-07-05',
            ],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $registroResponse->assertStatus(200);

        $evento = EventoAcesso::query()->where('eva_col_id', $colaboradorId)->first();
        $this->assertNotNull($evento);

        $gestorNaoAutorizado = Usuario::query()
            ->where('usr_pes_id', $gestorNaoAutorizadoPessoaId)
            ->first();
        $this->be($gestorNaoAutorizado);

        $approveResponse = $this->call('POST', '/rh/aprovacoes-ponto/aprovar/' . $evento->eva_id);
        $approveResponse->assertRedirect('/rh/aprovacoes-ponto');

        $this->assertDatabaseHas('reh_eventos_acesso', [
            'eva_id' => $evento->eva_id,
            'eva_status' => 'pendente',
        ]);

        $this->assertDatabaseMissing('reh_aprovacoes_ponto', [
            'app_eva_id' => $evento->eva_id,
        ]);
    }

    private function criarSchemaMinimo(): void
    {
        Schema::create('gra_pessoas', function (Blueprint $table) {
            $table->increments('pes_id');
            $table->string('pes_nome');
            $table->string('pes_email')->unique();
            $table->date('pes_nascimento')->nullable();
            $table->timestamps();
        });

        Schema::create('seg_usuarios', function (Blueprint $table) {
            $table->increments('usr_id');
            $table->string('usr_usuario');
            $table->string('usr_senha')->nullable();
            $table->boolean('usr_ativo')->default(true);
            $table->integer('usr_pes_id')->unsigned()->nullable();
            $table->integer('usr_profile_picture_id')->unsigned()->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('seg_auditoria', function (Blueprint $table) {
            $table->increments('log_id');
            $table->integer('log_usr_id')->unsigned()->nullable();
            $table->string('log_action');
            $table->string('log_table');
            $table->integer('log_table_id')->unsigned()->nullable();
            $table->longText('log_object')->nullable();
            $table->timestamps();
        });

        Schema::create('reh_colaboradores', function (Blueprint $table) {
            $table->increments('col_id');
            $table->integer('col_pes_id')->unsigned()->nullable();
            $table->string('col_status')->default('ativo');
            $table->integer('col_gestor_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('reh_setores', function (Blueprint $table) {
            $table->increments('set_id');
            $table->string('set_descricao');
            $table->string('set_sigla')->nullable();
            $table->timestamps();
        });

        Schema::create('reh_colaboradores_funcoes', function (Blueprint $table) {
            $table->increments('cfn_id');
            $table->integer('cfn_fun_id')->unsigned()->nullable();
            $table->integer('cfn_col_id')->unsigned();
            $table->integer('cfn_set_id')->unsigned();
            $table->date('cfn_data_inicio')->nullable();
            $table->date('cfn_data_fim')->nullable();
            $table->timestamps();
        });

        Schema::create('reh_gestores_setor', function (Blueprint $table) {
            $table->increments('gst_id');
            $table->integer('gst_col_id')->unsigned();
            $table->integer('gst_set_id')->unsigned();
            $table->boolean('gst_ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('reh_configuracoes_ponto', function (Blueprint $table) {
            $table->increments('cfg_id');
            $table->string('cfg_chave')->unique();
            $table->text('cfg_valor');
            $table->string('cfg_descricao')->nullable();
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

        Schema::create('reh_aprovacoes_ponto', function (Blueprint $table) {
            $table->increments('app_id');
            $table->integer('app_eva_id')->unsigned();
            $table->integer('app_aprovador_col_id')->unsigned();
            $table->dateTime('app_data_aprovacao');
            $table->enum('app_status', ['aprovado', 'reprovado']);
            $table->text('app_motivo')->nullable();
            $table->dateTime('app_hora_ajustada')->nullable();
            $table->timestamps();
        });
    }

    private function seedConfiguracoes(): void
    {
        foreach ([
            'ponto.janela_inicio' => '05:00',
            'ponto.janela_fim' => '23:00',
            'ponto.anti_duplicidade_seg' => '60',
            'ponto.retry_max_tentativas' => '3',
            'ponto.retry_delay_min' => '5',
        ] as $chave => $valor) {
            \Illuminate\Support\Facades\DB::table('reh_configuracoes_ponto')->insert([
                'cfg_chave' => $chave,
                'cfg_valor' => $valor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function criarSetor(string $nome): int
    {
        return \Illuminate\Support\Facades\DB::table('reh_setores')->insertGetId([
            'set_descricao' => $nome,
            'set_sigla' => strtoupper(substr($nome, 0, 3)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function criarColaborador(string $nome, string $email, string $nascimento): array
    {
        $pessoaId = \Illuminate\Support\Facades\DB::table('gra_pessoas')->insertGetId([
            'pes_nome' => $nome,
            'pes_email' => $email,
            'pes_nascimento' => $nascimento,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $colaboradorId = \Illuminate\Support\Facades\DB::table('reh_colaboradores')->insertGetId([
            'col_pes_id' => $pessoaId,
            'col_status' => 'ativo',
            'col_gestor_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$pessoaId, $colaboradorId];
    }

    private function criarUsuario(int $pessoaId, string $usuario): void
    {
        Usuario::query()->create([
            'usr_usuario' => $usuario,
            'usr_senha' => bcrypt('secret'),
            'usr_ativo' => true,
            'usr_pes_id' => $pessoaId,
        ]);
    }

    private function vincularSetorAtual(int $colaboradorId, int $setorId): void
    {
        \Illuminate\Support\Facades\DB::table('reh_colaboradores_funcoes')->insert([
            'cfn_col_id' => $colaboradorId,
            'cfn_set_id' => $setorId,
            'cfn_data_inicio' => '2026-01-01',
            'cfn_data_fim' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vincularGestorAoSetor(int $gestorColaboradorId, int $setorId): void
    {
        \Illuminate\Support\Facades\DB::table('reh_gestores_setor')->insert([
            'gst_col_id' => $gestorColaboradorId,
            'gst_set_id' => $setorId,
            'gst_ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
