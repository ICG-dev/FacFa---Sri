<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ConsultarAutorizacionJob;
use App\Jobs\EnviarComprobanteRecepcionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Sri\SriRecepcionClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnviarComprobanteRecepcionJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recibida_despacha_el_job_de_consulta_de_autorizacion(): void
    {
        Bus::fake([ConsultarAutorizacionJob::class]);

        $factura = Factura::factory()->for(Empresa::factory())->create([
            'estado' => Factura::ESTADO_ENVIADA,
            'ambiente' => 1,
            'clave_acceso' => str_repeat('1', 49),
            'xml_firmado' => '<factura></factura>',
        ]);

        $cliente = Mockery::mock(SriRecepcionClient::class);
        $cliente->shouldReceive('enviar')->once()->andReturn(['estado' => 'RECIBIDA', 'mensajes' => []]);

        (new EnviarComprobanteRecepcionJob($factura))->handle($cliente);

        Bus::assertDispatched(ConsultarAutorizacionJob::class);
        $this->assertSame(Factura::ESTADO_ENVIADA, $factura->fresh()->estado);
    }

    #[Test]
    public function devuelta_marca_la_factura_como_devuelta_con_los_mensajes_del_sri(): void
    {
        $factura = Factura::factory()->for(Empresa::factory())->create([
            'estado' => Factura::ESTADO_ENVIADA,
            'ambiente' => 1,
            'clave_acceso' => str_repeat('1', 49),
            'xml_firmado' => '<factura></factura>',
        ]);

        $mensajes = [['identificador' => '52', 'mensaje' => 'ERROR EN DIFERENCIAS', 'tipo' => 'ERROR']];

        $cliente = Mockery::mock(SriRecepcionClient::class);
        $cliente->shouldReceive('enviar')->once()->andReturn(['estado' => 'DEVUELTA', 'mensajes' => $mensajes]);

        (new EnviarComprobanteRecepcionJob($factura))->handle($cliente);

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_DEVUELTA, $factura->estado);
        $this->assertSame($mensajes, $factura->mensajes_error);
    }

    #[Test]
    public function fallo_final_deja_advertencia_sin_sobrescribir_facturas_resueltas(): void
    {
        $factura = Factura::factory()->create(['estado' => Factura::ESTADO_ENVIADA]);
        $job = new EnviarComprobanteRecepcionJob($factura);
        $job->failed(new \RuntimeException('Timeout'));

        $this->assertSame(Factura::ESTADO_DEVUELTA, $factura->fresh()->estado);
        $this->assertSame('ADVERTENCIA', $factura->fresh()->mensajes_error[0]['tipo']);

        $factura->update(['estado' => Factura::ESTADO_AUTORIZADA, 'mensajes_error' => null]);
        $job->failed(new \RuntimeException('Timeout'));
        $this->assertSame(Factura::ESTADO_AUTORIZADA, $factura->fresh()->estado);
        $this->assertNull($factura->fresh()->mensajes_error);
    }


    #[Test]
    public function clave_registrada_consulta_autorizacion_y_omite_envios_resueltos(): void
    {
        Bus::fake([ConsultarAutorizacionJob::class]);
        $factura = Factura::factory()->create([
            'estado' => Factura::ESTADO_ENVIADA,
            'ambiente' => 1,
            'xml_firmado' => '<factura/>',
        ]);
        $cliente = Mockery::mock(SriRecepcionClient::class);
        $cliente->shouldReceive('enviar')->once()->andReturn([
            'estado' => 'DEVUELTA',
            'mensajes' => [['identificador' => '43']],
        ]);
        $job = new EnviarComprobanteRecepcionJob($factura);
        $job->handle($cliente);
        Bus::assertDispatchedTimes(ConsultarAutorizacionJob::class, 1);
        $this->assertSame(Factura::ESTADO_ENVIADA, $factura->fresh()->estado);

        $factura->update(['estado' => Factura::ESTADO_AUTORIZADA]);
        $job->handle($cliente);
        Bus::assertDispatchedTimes(ConsultarAutorizacionJob::class, 1);
    }
}
