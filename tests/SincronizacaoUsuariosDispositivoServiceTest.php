<?php

use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\MapeamentoDispositivoRepository;
use Modulos\RH\Services\ControlIdApiClient;
use Modulos\RH\Services\SincronizacaoUsuariosDispositivoService;

class SincronizacaoUsuariosDispositivoServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testCombinarUsuariosComMapeamentosMontaStatusEsperado(): void
    {
        $service = new SincronizacaoUsuariosDispositivoService(
            $this->createMock(ControlIdApiClient::class),
            $this->getMockBuilder(ColaboradorRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(MapeamentoDispositivoRepository::class)->disableOriginalConstructor()->getMock()
        );

        $usuarios = [
            [
                'id' => 1,
                'registration' => '10',
                'name' => 'Maria',
                'image_timestamp' => '123456',
            ],
            [
                'id' => 2,
                'registration' => '20',
                'name' => 'Joao',
            ],
        ];

        $mapeamentos = [
            [
                'map_user_id' => '1',
                'map_col_id' => 44,
                'colaborador_nome' => 'Maria da Silva',
                'map_ativo' => true,
            ],
        ];

        $combinados = $service->combinarUsuariosComMapeamentos($usuarios, $mapeamentos);

        $this->assertCount(2, $combinados);
        $this->assertSame('vinculado', $combinados[0]['status_vinculo']);
        $this->assertTrue($combinados[0]['foto_ok']);
        $this->assertSame('Maria da Silva', $combinados[0]['colaborador_nome']);
        $this->assertSame('pendente', $combinados[1]['status_vinculo']);
        $this->assertFalse($combinados[1]['foto_ok']);
    }

    public function testResumirUsuariosContaStatus(): void
    {
        $service = new SincronizacaoUsuariosDispositivoService(
            $this->createMock(ControlIdApiClient::class),
            $this->getMockBuilder(ColaboradorRepository::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(MapeamentoDispositivoRepository::class)->disableOriginalConstructor()->getMock()
        );

        $resumo = $service->resumirUsuarios([
            ['col_id' => 1, 'foto_ok' => true],
            ['col_id' => null, 'foto_ok' => false],
            ['col_id' => 2, 'foto_ok' => false],
        ]);

        $this->assertSame([
            'total' => 3,
            'vinculados' => 2,
            'pendentes' => 1,
            'com_foto' => 1,
            'sem_foto' => 2,
        ], $resumo);
    }
}
