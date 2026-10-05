<?php

namespace Tests\Unit;

use App\Models\Factura;
use App\Services\Sri\AnulacionElegibilidadService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnulacionElegibilidadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function consumidor_final_nunca_puede_anularse_sin_importar_la_fecha(): void
    {
        CarbonImmutable::setTestNow('2026-02-02');

        $factura = new Factura([
            'fecha_emision' => '2026-01-15',
            'identificacion_comprador' => config('sri.identificacion_consumidor_final'),
        ]);

        $service = new AnulacionElegibilidadService;

        $this->assertSame(AnulacionElegibilidadService::VIA_NO_DISPONIBLE, $service->via($factura));
    }

    #[Test]
    public function dentro_del_plazo_de_7_dias_del_mes_siguiente_la_via_es_en_linea(): void
    {
        CarbonImmutable::setTestNow('2026-02-02');

        $factura = new Factura([
            'fecha_emision' => '2026-01-15',
            'identificacion_comprador' => '0999999999',
        ]);

        $service = new AnulacionElegibilidadService;

        $this->assertSame(AnulacionElegibilidadService::VIA_EN_LINEA, $service->via($factura));
    }

    #[Test]
    public function pasado_el_dia_7_del_mes_siguiente_la_via_es_nota_de_credito(): void
    {
        CarbonImmutable::setTestNow('2026-02-10');

        $factura = new Factura([
            'fecha_emision' => '2026-01-15',
            'identificacion_comprador' => '0999999999',
        ]);

        $service = new AnulacionElegibilidadService;

        $this->assertSame(AnulacionElegibilidadService::VIA_NOTA_CREDITO, $service->via($factura));
    }

    #[Test]
    public function pasados_12_meses_ya_no_hay_via_disponible(): void
    {
        CarbonImmutable::setTestNow('2027-09-20');

        $factura = new Factura([
            'fecha_emision' => '2026-01-15',
            'identificacion_comprador' => '0999999999',
        ]);

        $service = new AnulacionElegibilidadService;

        $this->assertSame(AnulacionElegibilidadService::VIA_NO_DISPONIBLE, $service->via($factura));
    }

    #[Test]
    public function los_plazos_incluyen_el_ultimo_dia_completo(): void
    {
        $factura = new Factura(['fecha_emision' => '2026-01-15', 'identificacion_comprador' => '0999999999']);
        $service = new AnulacionElegibilidadService;

        CarbonImmutable::setTestNow('2026-02-07 18:00:00');
        $this->assertSame(AnulacionElegibilidadService::VIA_EN_LINEA, $service->via($factura));
        CarbonImmutable::setTestNow('2026-02-08 00:00:00');
        $this->assertSame(AnulacionElegibilidadService::VIA_NOTA_CREDITO, $service->via($factura));
        CarbonImmutable::setTestNow('2027-01-15 23:59:59');
        $this->assertSame(AnulacionElegibilidadService::VIA_NOTA_CREDITO, $service->via($factura));
        CarbonImmutable::setTestNow('2027-01-16 00:00:00');
        $this->assertSame(AnulacionElegibilidadService::VIA_NO_DISPONIBLE, $service->via($factura));
    }
}
