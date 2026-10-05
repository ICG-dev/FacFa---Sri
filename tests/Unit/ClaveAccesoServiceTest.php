<?php

namespace Tests\Unit;

use App\Services\Sri\ClaveAccesoService;
use PHPUnit\Framework\TestCase;

class ClaveAccesoServiceTest extends TestCase
{
    



    public function test_digito_verificador_contra_ejemplo_oficial_del_sri(): void
    {
        $service = new ClaveAccesoService;

        $this->assertSame(6, $service->digitoVerificador('41261533'));
    }

    public function test_digito_verificador_es_cero_cuando_el_resultado_es_once(): void
    {
        $service = new ClaveAccesoService;

        
        $this->assertSame(0, $service->digitoVerificador('0000000000000000000000000000000000000000000000'));
    }
}
