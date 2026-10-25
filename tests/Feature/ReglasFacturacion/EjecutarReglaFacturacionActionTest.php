<?php

namespace Tests\Feature\ReglasFacturacion;

use App\Actions\ReglasFacturacion\EjecutarReglaFacturacionAction;
use App\Jobs\EnviarComprobanteRecepcionJob;
use App\Jobs\ProcesarReglaFacturacionJob;
use App\Models\Factura;
use App\Models\Producto;
use App\Services\Sri\XadesSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\CreaReglaFacturacion;
use Tests\TestCase;

class EjecutarReglaFacturacionActionTest extends TestCase
{
    use CreaReglaFacturacion, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(8, 0));
        Bus::fake([EnviarComprobanteRecepcionJob::class]);
    }

    public function test_revision_totales_e_idempotencia(): void
    {
        $regla = $this->crearRegla();
        $accion = app(EjecutarReglaFacturacionAction::class);
        $this->assertSame(1, $accion->ejecutar($regla)['exitosos']);
        $factura = Factura::sole();
        $this->assertSame('borrador', $factura->estado);
        $this->assertSame('21.85', $factura->importe_total);
        $this->assertSame('19.00', $factura->detalles->sole()->precio_total_sin_impuesto);
        $this->assertSame(1, $accion->ejecutar($regla)['omitidos']);
        $this->assertSame(1, Factura::count());
        Bus::assertNotDispatched(EnviarComprobanteRecepcionJob::class);
    }

    public function test_plazo_de_credito_se_conserva_en_factura_y_xml(): void
    {
        $regla = $this->crearRegla(['tipo_emision' => 'automatica', 'tiempo_plazo' => 30]);
        $this->mock(XadesSigner::class)->shouldReceive('firmar')->once()->andReturn('<firmado/>');
        app(EjecutarReglaFacturacionAction::class)->ejecutar($regla);
        $factura = Factura::sole();
        $this->assertSame(30, $factura->tiempo_plazo);
        $this->assertStringContainsString('<plazo>30</plazo>', $factura->xml_generado);
        $this->assertStringContainsString('<unidadTiempo>dias</unidadTiempo>', $factura->xml_generado);
    }

    public function test_fallo_aislado_y_reintento_actualiza_auditoria(): void
    {
        $regla = $this->crearRegla();
        $cliente = $regla->clientes()->create(['tipo_identificacion' => '05', 'identificacion' => '123', 'razon_social' => 'Inválido']);
        $accion = app(EjecutarReglaFacturacionAction::class);
        $resultado = $accion->ejecutar($regla);
        $this->assertSame(1, $resultado['fallidos']);
        $this->assertSame(1, $resultado['exitosos']);
        $this->assertSame(1, Factura::count());
        $cliente->update(['tipo_identificacion' => '06', 'direccion' => 'Quito', 'correo' => 'otro@example.com']);
        $this->assertSame(1, $accion->ejecutar($regla)['exitosos']);
        $this->assertSame(2, $regla->ejecuciones()->where('estado', 'exitoso')->count());
        $this->assertSame(2, Factura::count());
    }

    public function test_automatica_reutiliza_confirmacion_y_revierte_si_falla_firma(): void
    {
        $regla = $this->crearRegla(['tipo_emision' => 'automatica']);
        $this->mock(XadesSigner::class)->shouldReceive('firmar')->once()->andThrow(new \RuntimeException('Certificado inválido'));
        $this->assertSame(1, app(EjecutarReglaFacturacionAction::class)->ejecutar($regla)['fallidos']);
        $this->assertSame(0, Factura::count());
        $this->assertSame(0, $regla->empresa->ultimo_secuencial);
        Bus::assertNotDispatched(EnviarComprobanteRecepcionJob::class);
        $this->mock(XadesSigner::class)->shouldReceive('firmar')->once()->andReturn('<ds:Signature/>');
        $this->assertSame(1, app(EjecutarReglaFacturacionAction::class)->ejecutar($regla)['exitosos']);
        $factura = Factura::sole();
        $this->assertSame('enviada', $factura->estado);
        $this->assertSame(49, strlen($factura->clave_acceso));
        $this->assertSame(1, $factura->secuencial);
        Bus::assertDispatched(EnviarComprobanteRecepcionJob::class, fn ($job) => $job->queue === 'sri' && $job->afterCommit === true);
    }

    public function test_rechaza_productos_de_otra_empresa(): void
    {
        $regla = $this->crearRegla();
        $regla->productos()->first()->update(['producto_id' => Producto::factory()->create()->id]);
        $this->assertSame(1, app(EjecutarReglaFacturacionAction::class)->ejecutar($regla)['fallidos']);
        $this->assertSame(0, Factura::count());
    }

    public function test_job_conserva_ciclo_original_y_empresa(): void
    {
        $regla = $this->crearRegla();
        $job = new ProcesarReglaFacturacionJob($regla->id, $regla->empresa_id, '2026-10-19', null, true);
        $regla->update(['proxima_ejecucion_at' => '2026-11-19']);
        $this->travelTo(now()->setDate(2026, 11, 1));
        $job->handle(app(EjecutarReglaFacturacionAction::class));
        $this->assertSame('2026-10', $regla->ejecuciones()->sole()->periodo);
        $this->assertSame(1, Factura::count());
    }

    public function test_no_ejecuta_reglas_pausadas_futuras_o_vencidas(): void
    {
        foreach ([['activo' => false], ['fecha_inicio' => '2026-11-01'], ['fecha_fin' => '2026-10-18']] as $datos) {
            $regla = $this->crearRegla($datos);
            $this->assertSame(0, app(EjecutarReglaFacturacionAction::class)->ejecutar($regla)['exitosos']);
        }
        $this->assertSame(0, Factura::count());
    }
}
