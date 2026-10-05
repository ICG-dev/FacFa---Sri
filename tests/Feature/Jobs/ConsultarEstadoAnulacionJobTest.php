<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ConsultarEstadoAnulacionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Sri\SriConsultaComprobanteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsultarEstadoAnulacionJobTest extends TestCase
{
    use RefreshDatabase;

    private function facturaPendienteAnular(): Factura
    {
        return Factura::factory()->for(Empresa::factory())->autorizada()->create([
            'estado' => Factura::ESTADO_PENDIENTE_ANULAR,
            'ambiente' => 1,
        ]);
    }

    #[Test]
    public function anulado_marca_la_factura_como_anulada(): void
    {
        $factura = $this->facturaPendienteAnular();

        $cliente = Mockery::mock(SriConsultaComprobanteClient::class);
        $cliente->shouldReceive('consultarEstadoAutorizacion')->once()->andReturn('ANULADO');

        (new ConsultarEstadoAnulacionJob($factura))->handle($cliente);

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_ANULADA, $factura->estado);
        $this->assertNotNull($factura->fecha_anulacion);
    }

    #[Test]
    public function autorizado_revierte_la_factura_a_autorizada_si_el_tramite_no_se_completo(): void
    {
        $factura = $this->facturaPendienteAnular();

        $cliente = Mockery::mock(SriConsultaComprobanteClient::class);
        $cliente->shouldReceive('consultarEstadoAutorizacion')->once()->andReturn('AUTORIZADO');

        (new ConsultarEstadoAnulacionJob($factura))->handle($cliente);

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_AUTORIZADA, $factura->estado);
        $this->assertNotEmpty($factura->mensajes_error);
    }

    #[Test]
    public function pendiente_de_anular_no_cambia_el_estado_todavia(): void
    {
        $factura = $this->facturaPendienteAnular();

        $cliente = Mockery::mock(SriConsultaComprobanteClient::class);
        $cliente->shouldReceive('consultarEstadoAutorizacion')->once()->andReturn('PENDIENTE DE ANULAR');

        (new ConsultarEstadoAnulacionJob($factura))->handle($cliente);

        $this->assertSame(Factura::ESTADO_PENDIENTE_ANULAR, $factura->fresh()->estado);
    }
}
