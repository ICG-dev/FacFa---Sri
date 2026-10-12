<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->create();
        $this->usuario->assignRole('facturador');
        $this->actingAs($this->usuario);
    }

    #[Test]
    public function muestra_el_numero_formal_y_la_clave_completa_para_copiar(): void
    {
        $clave = str_repeat('1234567', 7);
        $this->crearFactura([
            'estado' => Factura::ESTADO_RECHAZADA,
            'establecimiento' => '001',
            'punto_emision' => '002',
            'secuencial' => 77,
            'clave_acceso' => $clave,
        ]);

        Livewire::test(FacturaIndex::class)
            ->assertSee('Comprobante')
            ->assertSee('001-002-000000077')
            ->assertSee($clave)
            ->assertSee('Copiar clave de acceso');
    }

    #[Test]
    public function muestra_borrador_si_no_tiene_secuencial(): void
    {
        $this->crearFactura(['secuencial' => null]);

        Livewire::test(FacturaIndex::class)
            ->assertSee('Comprobante')
            ->assertSee('Borrador')
            ->assertDontSee('000-000-000000000');
    }

    #[Test]
    #[DataProvider('estadosConErrores')]
    public function muestra_el_motivo_del_sri_y_escapa_la_informacion_adicional(string $estado): void
    {
        $this->crearFactura([
            'estado' => $estado,
            'mensajes_error' => [[
                'mensaje' => 'COMPROBANTE RECHAZADO',
                'informacionAdicional' => '<script>alert("SRI")</script>',
            ]],
        ]);

        Livewire::test(FacturaIndex::class)
            ->assertSee('Ver motivo del SRI')
            ->assertSee('COMPROBANTE RECHAZADO')
            ->assertSee('<script>alert("SRI")</script>')
            ->assertDontSee('<script>alert("SRI")</script>', escape: false);
    }

    public static function estadosConErrores(): array
    {
        return [['rechazada'], ['devuelta']];
    }

    #[Test]
    public function los_errores_sin_mensaje_muestran_la_respuesta_disponible(): void
    {
        $this->crearFactura([
            'estado' => Factura::ESTADO_RECHAZADA,
            'mensajes_error' => [['identificador' => '35']],
        ]);

        Livewire::test(FacturaIndex::class)->assertSee('{"identificador":"35"}');
    }

    #[Test]
    public function sin_errores_no_muestra_el_indicador_del_sri(): void
    {
        $this->crearFactura(['estado' => Factura::ESTADO_RECHAZADA, 'mensajes_error' => null]);

        Livewire::test(FacturaIndex::class)->assertDontSee('Ver motivo del SRI');
    }

    private function crearFactura(array $datos): Factura
    {
        return Factura::factory()->create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
        ], $datos));
    }
}
