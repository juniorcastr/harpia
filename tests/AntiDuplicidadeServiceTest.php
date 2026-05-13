<?php

use Modulos\RH\Services\AntiDuplicidadeService;

class AntiDuplicidadeServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGeraMesmoHashParaMesmoUsuarioDispositivoEMinuto(): void
    {
        $service = new AntiDuplicidadeService();

        $hashA = $service->gerarHash('25', '478435', '2026-05-13 08:30:01');
        $hashB = $service->gerarHash('25', '478435', '2026-05-13 08:30:59');

        $this->assertSame($hashA, $hashB);
    }

    public function testGeraHashDiferenteQuandoMinutoMuda(): void
    {
        $service = new AntiDuplicidadeService();

        $hashA = $service->gerarHash('25', '478435', '2026-05-13 08:30:59');
        $hashB = $service->gerarHash('25', '478435', '2026-05-13 08:31:00');

        $this->assertNotSame($hashA, $hashB);
    }
}
