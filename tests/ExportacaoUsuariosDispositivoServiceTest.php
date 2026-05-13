<?php

use Modulos\RH\Repositories\ColaboradorRepository;
use Modulos\RH\Services\ExportacaoUsuariosDispositivoService;

class ExportacaoUsuariosDispositivoServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testMontarCsvGeraEstruturaCompativelComControlId(): void
    {
        $repository = $this->getMockBuilder(ColaboradorRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service = new ExportacaoUsuariosDispositivoService($repository);

        $csv = $service->montarCsv([
            [
                'registration' => '1',
                'name' => 'Ana Souza',
            ],
            [
                'registration' => '2',
                'name' => 'Bruno Lima',
            ],
        ]);

        $this->assertStringContainsString('cid_metadata', $csv);
        $this->assertStringContainsString("users\n", str_replace("\r\n", "\n", $csv));
        $this->assertStringContainsString('1,Ana Souza', str_replace('"', '', $csv));
        $this->assertStringContainsString('2,Bruno Lima', str_replace('"', '', $csv));
        $this->assertStringContainsString('face_templates', $csv);
    }
}
