<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Services\ColetaEventosDispositivoService;
use Modulos\RH\Services\ControlIdApiClient;
use Modulos\RH\Services\EventoAcessoService;

class ColetaEventosDispositivoServiceTest extends \TestCase
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

    public function testColetaAtualizaCursorPersistenteEBuscaApenasNovosLogs(): void
    {
        $dispositivo = DispositivoAcesso::query()->create([
            'dis_nome' => 'Portaria Principal',
            'dis_identificador' => '478435',
            'dis_tipo' => 'entrada',
            'dis_ip' => '10.0.0.15',
            'dis_modelo' => 'iDFace',
            'dis_token_api' => str_repeat('d', 64),
            'dis_status' => 'ativo',
            'dis_ultimo_access_log_id' => 10,
        ]);

        $apiClient = $this->createMock(ControlIdApiClient::class);
        $eventoAcessoService = $this->createMock(EventoAcessoService::class);

        $apiClient->expects($this->exactly(2))
            ->method('loadObjects')
            ->willReturnMap([
                [$dispositivo, 'access_logs', [
                    'limit' => 2,
                    'order' => ['id', 'ascending'],
                    'where' => [
                        [
                            'object' => 'access_logs',
                            'field' => 'id',
                            'operator' => '>',
                            'value' => 10,
                        ],
                    ],
                ], [
                    'access_logs' => [
                        ['id' => 11, 'time' => '1715600000', 'event' => 7, 'user_id' => 1, 'device_id' => 478435],
                        ['id' => 12, 'time' => '1715600300', 'event' => 7, 'user_id' => 1, 'device_id' => 478435],
                    ],
                ]],
                [$dispositivo, 'access_logs', [
                    'limit' => 2,
                    'order' => ['id', 'ascending'],
                    'where' => [
                        [
                            'object' => 'access_logs',
                            'field' => 'id',
                            'operator' => '>',
                            'value' => 12,
                        ],
                    ],
                ], [
                    'access_logs' => [],
                ]],
            ]);

        $eventoAcessoService->expects($this->once())
            ->method('processarLoteAccessLogs')
            ->with(
                $dispositivo,
                [
                    ['id' => 11, 'time' => '1715600000', 'event' => 7, 'user_id' => 1, 'device_id' => 478435],
                    ['id' => 12, 'time' => '1715600300', 'event' => 7, 'user_id' => 1, 'device_id' => 478435],
                ],
                [
                    'ip' => '10.0.0.15',
                    'user_agent' => 'polling-controlid',
                ]
            )
            ->willReturn([
                'processado' => 2,
                'erro' => 0,
                'duplicado' => 0,
                'ignorado' => 0,
            ]);

        $service = new ColetaEventosDispositivoService($apiClient, $eventoAcessoService);
        $resultado = $service->coletarNovosEventos($dispositivo, 2);

        $dispositivo->refresh();

        $this->assertSame(2, $resultado['lidos']);
        $this->assertSame(2, $resultado['processado']);
        $this->assertSame(12, $resultado['ultimo_id_atual']);
        $this->assertSame(12, (int) $dispositivo->dis_ultimo_access_log_id);
        $this->assertNotNull($dispositivo->dis_ultima_coleta_em);
    }

    private function criarSchemaMinimo(): void
    {
        Schema::create('reh_dispositivos_acesso', function (Blueprint $table) {
            $table->increments('dis_id');
            $table->string('dis_nome');
            $table->string('dis_identificador')->unique();
            $table->enum('dis_tipo', ['entrada', 'saida']);
            $table->string('dis_ip', 45)->nullable();
            $table->string('dis_modelo')->nullable();
            $table->string('dis_token_api', 64)->unique();
            $table->enum('dis_status', ['ativo', 'inativo'])->default('ativo');
            $table->unsignedBigInteger('dis_ultimo_access_log_id')->nullable();
            $table->dateTime('dis_ultima_coleta_em')->nullable();
            $table->text('dis_observacao')->nullable();
            $table->timestamps();
        });
    }
}
