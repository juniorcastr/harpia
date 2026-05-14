<?php

use Modulos\RH\Models\DispositivoAcesso;
use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Repositories\MapeamentoDispositivoRepository;
use Modulos\RH\Services\ControlIdApiClient;
use Modulos\RH\Services\SincronizacaoUsuariosDispositivoService;

class SincronizacaoUsuariosDispositivoDepartamentoPadraoTest extends \TestCase
{
    public function testCriacaoDeUsuarioVinculaGrupoPadraoQuandoAusente(): void
    {
        $apiClient = $this->createMock(ControlIdApiClient::class);
        $colaboradorRepository = $this->getMockBuilder(ColaboradorRepository::class)->disableOriginalConstructor()->getMock();
        $mapeamentoRepository = $this->getMockBuilder(MapeamentoDispositivoRepository::class)->disableOriginalConstructor()->getMock();

        $service = new SincronizacaoUsuariosDispositivoService(
            $apiClient,
            $colaboradorRepository,
            $mapeamentoRepository
        );

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_id = 1;

        $apiClient->expects($this->exactly(2))
            ->method('loadObjects')
            ->willReturnMap([
                [$dispositivo, 'groups', ['fields' => ['id', 'name'], 'order' => ['id', 'ascending']], [
                    'groups' => [
                        ['id' => 1, 'name' => 'Padrão'],
                    ],
                ]],
                [$dispositivo, 'user_groups', ['where' => ['user_groups' => ['user_id' => 99]]], [
                    'user_groups' => [],
                ]],
            ]);

        $apiClient->expects($this->once())
            ->method('createUserGroup')
            ->with($dispositivo, 99, 1)
            ->willReturn(['changes' => 1]);

        $this->invocarGarantiaDeGrupoPadrao($service, $dispositivo, '99');
    }

    public function testUsuarioJaVinculadoAoGrupoPadraoNaoCriaNovoRelacionamento(): void
    {
        $apiClient = $this->createMock(ControlIdApiClient::class);
        $colaboradorRepository = $this->getMockBuilder(ColaboradorRepository::class)->disableOriginalConstructor()->getMock();
        $mapeamentoRepository = $this->getMockBuilder(MapeamentoDispositivoRepository::class)->disableOriginalConstructor()->getMock();

        $service = new SincronizacaoUsuariosDispositivoService(
            $apiClient,
            $colaboradorRepository,
            $mapeamentoRepository
        );

        $dispositivo = new DispositivoAcesso();
        $dispositivo->dis_id = 1;

        $apiClient->expects($this->exactly(2))
            ->method('loadObjects')
            ->willReturnMap([
                [$dispositivo, 'groups', ['fields' => ['id', 'name'], 'order' => ['id', 'ascending']], [
                    'groups' => [
                        ['id' => 1, 'name' => 'Padrão'],
                    ],
                ]],
                [$dispositivo, 'user_groups', ['where' => ['user_groups' => ['user_id' => 100]]], [
                    'user_groups' => [
                        ['user_id' => 100, 'group_id' => 1],
                    ],
                ]],
            ]);

        $apiClient->expects($this->never())
            ->method('createUserGroup');

        $this->invocarGarantiaDeGrupoPadrao($service, $dispositivo, '100');
    }

    private function invocarGarantiaDeGrupoPadrao(
        SincronizacaoUsuariosDispositivoService $service,
        DispositivoAcesso $dispositivo,
        string $userId
    ): void {
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('garantirGrupoPadraoDoUsuario');
        $method->setAccessible(true);

        $method->invoke($service, $dispositivo, $userId);
    }
}
