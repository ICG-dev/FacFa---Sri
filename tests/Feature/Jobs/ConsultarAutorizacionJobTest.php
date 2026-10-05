<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ConsultarAutorizacionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Sri\SriAutorizacionClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsultarAutorizacionJobTest extends TestCase
{
    use RefreshDatabase;

    private function facturaEnviada(array $atributos = []): Factura
    {
        return Factura::factory()->for(Empresa::factory())->create(array_merge([
            'estado' => Factura::ESTADO_ENVIADA,
            'ambiente' => 1,
            'clave_acceso' => str_repeat('1', 49),
            'enviado_at' => now(),
        ], $atributos));
    }

    #[Test]
    public function autorizado_actualiza_la_factura_con_los_datos_de_autorizacion(): void
    {
        $factura = $this->facturaEnviada();

        $cliente = Mockery::mock(SriAutorizacionClient::class);
        $cliente->shouldReceive('consultar')->once()->andReturn([
            'estado' => 'AUTORIZADO',
            'numeroAutorizacion' => $factura->clave_acceso,
            'fechaAutorizacion' => '2026-02-02T10:00:00-05:00',
            'xmlAutorizado' => '<factura>autorizada</factura>',
            'mensajes' => [],
        ]);

        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_AUTORIZADA, $factura->estado);
        $this->assertSame($factura->clave_acceso, $factura->numero_autorizacion);
        $this->assertSame('<factura>autorizada</factura>', $factura->xml_autorizado);
        $this->assertNotNull($factura->fecha_autorizacion);
    }

    #[Test]
    public function rechazado_marca_la_factura_como_rechazada(): void
    {
        $factura = $this->facturaEnviada();

        $mensajes = [['identificador' => '52', 'mensaje' => 'ERROR EN DIFERENCIAS', 'tipo' => 'ERROR']];

        $cliente = Mockery::mock(SriAutorizacionClient::class);
        $cliente->shouldReceive('consultar')->once()->andReturn([
            'estado' => 'NO AUTORIZADO',
            'numeroAutorizacion' => null,
            'fechaAutorizacion' => null,
            'xmlAutorizado' => null,
            'mensajes' => $mensajes,
        ]);

        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_RECHAZADA, $factura->estado);
        $this->assertSame($mensajes, $factura->mensajes_error);
    }

    #[Test]
    public function en_proceso_reintenta_con_backoff_dentro_del_plazo_maximo(): void
    {
        Bus::fake([ConsultarAutorizacionJob::class]);

        $factura = $this->facturaEnviada(['enviado_at' => now()->subHour()]);

        $cliente = Mockery::mock(SriAutorizacionClient::class);
        $cliente->shouldReceive('consultar')->once()->andReturn([
            'estado' => 'EN PROCESO',
            'numeroAutorizacion' => null,
            'fechaAutorizacion' => null,
            'xmlAutorizado' => null,
            'mensajes' => [],
        ]);

        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        Bus::assertDispatched(ConsultarAutorizacionJob::class);
        $this->assertSame(Factura::ESTADO_ENVIADA, $factura->fresh()->estado);
    }

    #[Test]
    public function en_proceso_deja_de_reintentar_tras_el_plazo_maximo_de_24_horas(): void
    {
        Bus::fake([ConsultarAutorizacionJob::class]);

        $factura = $this->facturaEnviada(['enviado_at' => now()->subHours(25)]);

        $cliente = Mockery::mock(SriAutorizacionClient::class);
        $cliente->shouldReceive('consultar')->once()->andReturn([
            'estado' => 'EN PROCESO',
            'numeroAutorizacion' => null,
            'fechaAutorizacion' => null,
            'xmlAutorizado' => null,
            'mensajes' => [],
        ]);

        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        Bus::assertNotDispatched(ConsultarAutorizacionJob::class);
        $factura->refresh();
        $this->assertSame(Factura::ESTADO_ENVIADA, $factura->estado);
        $this->assertNotEmpty($factura->mensajes_error);
    }

    #[Test]
    public function un_fallo_soap_reintenta_solo_dentro_del_plazo(): void
    {
        Bus::fake([ConsultarAutorizacionJob::class]);
        $factura = $this->facturaEnviada();
        $cliente = Mockery::mock(SriAutorizacionClient::class);
        $cliente->shouldReceive('consultar')->twice()->andThrow(new \SoapFault('Server', 'Timeout'));

        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        Bus::assertDispatched(ConsultarAutorizacionJob::class, fn ($job) => $job->queue === 'sri' && $job->delay->isFuture());
        $factura->update(['enviado_at' => now()->subHours(25)]);
        (new ConsultarAutorizacionJob($factura))->handle($cliente);

        Bus::assertDispatchedTimes(ConsultarAutorizacionJob::class, 1);
        $this->assertNotEmpty($factura->fresh()->mensajes_error);
    }
}
