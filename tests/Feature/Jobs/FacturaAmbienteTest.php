<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ConsultarAutorizacionJob;
use App\Jobs\ConsultarEstadoAnulacionJob;
use App\Jobs\EnviarComprobanteRecepcionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Sri\SriAutorizacionClient;
use App\Services\Sri\SriConsultaComprobanteClient;
use App\Services\Sri\SriRecepcionClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaAmbienteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function los_jobs_conservan_el_ambiente_original_si_la_empresa_lo_cambia(): void
    {
        Bus::fake();
        $empresa = Empresa::factory()->create(['ambiente' => 2]);
        $factura = Factura::factory()->for($empresa)->create([
            'ambiente' => 1, 'estado' => Factura::ESTADO_ENVIADA,
            'clave_acceso' => str_repeat('1', 49), 'xml_firmado' => '<factura/>', 'enviado_at' => now(),
        ]);
        $recepcion = Mockery::mock(SriRecepcionClient::class);
        $recepcion->shouldReceive('enviar')->with('<factura/>', Mockery::type(Empresa::class), 1)
            ->once()->andReturn(['estado' => 'RECIBIDA', 'mensajes' => []]);
        (new EnviarComprobanteRecepcionJob($factura))->handle($recepcion);

        $autorizacion = Mockery::mock(SriAutorizacionClient::class);
        $autorizacion->shouldReceive('consultar')->with($factura->clave_acceso, Mockery::type(Empresa::class), 1)
            ->once()->andReturn(['estado' => 'EN PROCESAMIENTO']);
        (new ConsultarAutorizacionJob($factura))->handle($autorizacion);

        $factura->update(['estado' => Factura::ESTADO_PENDIENTE_ANULAR]);
        $consulta = Mockery::mock(SriConsultaComprobanteClient::class);
        $consulta->shouldReceive('consultarEstadoAutorizacion')->with($factura->clave_acceso, Mockery::type(Empresa::class), 1)
            ->once()->andReturn('PENDIENTE DE ANULAR');
        (new ConsultarEstadoAnulacionJob($factura))->handle($consulta);
        $this->assertSame(1, $factura->fresh()->ambiente);
    }

    #[Test]
    public function las_facturas_historicas_recuperan_el_ambiente_de_la_clave(): void
    {
        $factura = new Factura(['clave_acceso' => str_repeat('0', 23).'2'.str_repeat('0', 25)]);
        $this->assertSame(2, $factura->ambienteEmision());
        $factura->clave_acceso = null;
        $this->expectException(\DomainException::class);
        $factura->ambienteEmision();
    }
}
