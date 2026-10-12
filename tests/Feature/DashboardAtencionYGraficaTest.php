<?php

namespace Tests\Feature;

use App\Livewire\DashboardView;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardAtencionYGraficaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->empresa = Empresa::factory()->create();
        $this->usuario = User::factory()->for($this->empresa)->create();
        $this->usuario->assignRole('admin');
        $this->travelTo(now()->setDate(2026, 10, 3)->setTime(10, 0));
    }

    #[Test]
    public function muestra_seccion_requiere_atencion_con_facturas_rechazadas_y_sus_motivos(): void
    {
        $factura = $this->crearFactura([
            'estado' => Factura::ESTADO_RECHAZADA,
            'razon_social_comprador' => 'Cliente Rechazado S.A.',
            'establecimiento' => '001',
            'punto_emision' => '002',
            'secuencial' => 77,
            'mensajes_error' => ['ERROR 45: RUC DEL RECEPTOR NO SE ENCUENTRA ACTIVO'],
        ]);

        $this->actingAs($this->usuario)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Requiere atención')
            ->assertSee('Cliente Rechazado S.A.')
            ->assertSee('001-002-000000077')
            ->assertSee('03/10/2026')
            ->assertSee('ERROR 45: RUC DEL RECEPTOR NO SE ENCUENTRA ACTIVO')
            ->assertSee(route('facturas.show', $factura));
    }

    #[Test]
    public function muestra_mensajes_estructurados_del_sri_y_escapa_su_contenido(): void
    {
        $this->crearFactura([
            'estado' => Factura::ESTADO_DEVUELTA,
            'mensajes_error' => [[
                'identificador' => '35',
                'mensaje' => 'COMPROBANTE DEVUELTO',
                'informacionAdicional' => '<script>alert("SRI")</script>',
                'tipo' => 'ERROR',
            ]],
        ]);

        $this->actingAs($this->usuario)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Requiere atención')
            ->assertSee('[35]')
            ->assertSee('COMPROBANTE DEVUELTO')
            ->assertSee('<script>alert("SRI")</script>')
            ->assertDontSee('<script>alert("SRI")</script>', escape: false);
    }

    #[Test]
    public function atencion_incluye_solo_las_cinco_rechazadas_o_devueltas_mas_recientes_de_su_empresa(): void
    {
        $facturas = collect(range(1, 6))->map(fn ($minutos) => $this->crearFactura([
            'estado' => $minutos % 2 === 0 ? Factura::ESTADO_DEVUELTA : Factura::ESTADO_RECHAZADA,
            'updated_at' => now()->subMinutes($minutos),
        ]));
        $this->crearFactura(['estado' => Factura::ESTADO_BORRADOR]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA]);
        $this->crearFactura([
            'empresa_id' => Empresa::factory()->create()->id,
            'estado' => Factura::ESTADO_RECHAZADA,
            'razon_social_comprador' => 'Comprador privado de otra empresa',
        ]);

        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertViewHas('requierenAtencion')
            ->assertViewHas('requierenAtencion', fn ($atencion) => $atencion->pluck('id')->all() === $facturas->take(5)->pluck('id')->all())
            ->assertDontSee('Comprador privado de otra empresa');
    }

    #[Test]
    public function renderiza_grafica_o_resumen_de_facturacion_de_los_ultimos_dias(): void
    {
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'importe_total' => 150]);

        $this->actingAs($this->usuario)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Facturación de los últimos 7 días')
            ->assertSee('2026-10-03: $150.00')
            ->assertSee('height: 100%', escape: false);
    }

    #[Test]
    public function suma_por_fecha_solo_autorizadas_de_su_empresa_incluye_ceros_y_cruza_el_mes(): void
    {
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'importe_total' => 150.25]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'importe_total' => 49.75]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'fecha_emision' => '2026-09-30', 'importe_total' => 500]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'fecha_emision' => '2026-09-27', 'importe_total' => 25.50]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'fecha_emision' => '2026-09-26', 'importe_total' => 999]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'fecha_emision' => '2026-10-04', 'importe_total' => 999]);
        $this->crearFactura(['empresa_id' => Empresa::factory()->create()->id, 'estado' => Factura::ESTADO_AUTORIZADA, 'importe_total' => 999]);
        foreach ([Factura::ESTADO_BORRADOR, Factura::ESTADO_ENVIADA, Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA, Factura::ESTADO_ANULADA, Factura::ESTADO_PENDIENTE_ANULAR] as $estado) {
            $this->crearFactura(['estado' => $estado, 'importe_total' => 999]);
        }

        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertViewHas('diasSemana')
            ->assertViewHas('diasSemana', function ($dias) {
                $this->assertSame(['2026-09-27', '2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02', '2026-10-03'], $dias->pluck('fecha')->all());
                $this->assertSame([25.50, 0.0, 0.0, 500.0, 0.0, 0.0, 200.0], $dias->pluck('total')->all());

                return true;
            })
            ->assertViewHas('maxFacturacionDia', 500.0)
            ->assertSeeHtml('height: 40%')
            ->assertSeeHtml('height: 5.1%');
    }

    #[Test]
    public function sin_facturas_muestra_mensajes_neutros_y_siete_dias_sin_dividir_por_cero(): void
    {
        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertSee('No hay facturas rechazadas ni devueltas pendientes de atención.')
            ->assertSee('Aún no has emitido facturas al SRI.')
            ->assertSee('Facturación de los últimos 7 días')
            ->assertViewHas('diasSemana', fn ($dias) => $dias->count() === 7 && $dias->pluck('total')->all() === [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0])
            ->assertViewHas('maxFacturacionDia', 1)
            ->assertSeeHtml('height: 0%');
    }

    #[Test]
    public function si_el_sri_no_informa_motivo_muestra_un_mensaje_claro(): void
    {
        $this->crearFactura(['estado' => Factura::ESTADO_RECHAZADA, 'mensajes_error' => null]);

        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertSee('El SRI no proporcionó un motivo.');
    }

    #[Test]
    public function la_barra_mayor_ocupa_toda_la_altura_incluso_con_importes_menores_a_un_dolar(): void
    {
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'importe_total' => 0.50]);
        $this->crearFactura(['estado' => Factura::ESTADO_AUTORIZADA, 'fecha_emision' => '2026-10-02', 'importe_total' => 0.25]);

        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertViewHas('maxFacturacionDia', 0.50)
            ->assertSeeHtml('height: 100%')
            ->assertSeeHtml('height: 50%');
    }

    #[Test]
    public function muestra_los_dias_de_la_grafica_en_espanol(): void
    {
        Livewire::actingAs($this->usuario)->test(DashboardView::class)
            ->assertSee('lun. 28')
            ->assertSee('sáb. 3');
    }

    private function crearFactura(array $datos): Factura
    {
        return Factura::factory()->create(array_merge([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->usuario->id,
        ], $datos));
    }
}
