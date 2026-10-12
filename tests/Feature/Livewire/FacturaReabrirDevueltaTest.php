<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaForm;
use App\Livewire\Facturas\FacturaShow;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaReabrirDevueltaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    #[DataProvider('usuariosPermitidos')]
    public function un_usuario_permitido_puede_reabrir_una_factura_devuelta_y_editar_el_borrador(string $rol, bool $esCreador): void
    {
        $creador = User::factory()->create();
        $usuario = $esCreador ? $creador : User::factory()->create(['empresa_id' => $creador->empresa_id]);
        $usuario->assignRole($rol);
        $factura = Factura::factory()->create([
            'empresa_id' => $creador->empresa_id,
            'user_id' => $creador->id,
            'estado' => Factura::ESTADO_DEVUELTA,
            'mensajes_error' => [['mensaje' => 'Error en recepción']],
        ]);

        Livewire::actingAs($usuario)->test(FacturaShow::class, ['factura' => $factura])
            ->assertSee('Editar / Reabrir borrador')
            ->call('reabrirBorrador')
            ->assertRedirect(route('facturas.edit', $factura));

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->estado);
        $this->assertNull($factura->mensajes_error);
        $this->actingAs($usuario)->get(route('facturas.edit', $factura))->assertOk();
    }

    #[Test]
    #[DataProvider('usuariosPermitidos')]
    public function un_facturador_puede_reabrir_una_factura_rechazada_y_editar_el_borrador(string $rol, bool $esCreador): void
    {
        $creador = User::factory()->create();
        $usuario = $esCreador ? $creador : User::factory()->create(['empresa_id' => $creador->empresa_id]);
        $usuario->assignRole($rol);
        $factura = Factura::factory()->create([
            'empresa_id' => $creador->empresa_id,
            'user_id' => $creador->id,
            'estado' => Factura::ESTADO_RECHAZADA,
            'mensajes_error' => [['mensaje' => 'Error de autorización']],
        ]);

        $this->assertTrue($usuario->can('update', $factura));
        Livewire::actingAs($usuario)->test(FacturaShow::class, ['factura' => $factura])
            ->assertSee('Editar / Reabrir borrador')
            ->call('reabrirBorrador')
            ->assertRedirect(route('facturas.edit', $factura));

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->estado);
        $this->assertNull($factura->mensajes_error);
        $this->actingAs($usuario)->get(route('facturas.edit', $factura))->assertOk();
    }

    public static function usuariosPermitidos(): array
    {
        return [
            'facturador creador' => ['facturador', true],
            'admin creador' => ['admin', true],
            'admin de la empresa' => ['admin', false],
        ];
    }

    #[Test]
    #[DataProvider('estadosNoEditables')]
    public function los_estados_protegidos_no_permiten_edicion_reapertura_ni_reenvio(string $estado, string $rol): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole($rol);
        $mensajes = [['mensaje' => 'Error original']];
        $factura = Factura::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'user_id' => $usuario->id,
            'estado' => $estado,
            'mensajes_error' => $mensajes,
        ]);

        $this->assertFalse($usuario->can('update', $factura));
        $this->assertFalse($usuario->can('confirmar', $factura));
        $this->actingAs($usuario)->get(route('facturas.edit', $factura))->assertForbidden();
        Livewire::actingAs($usuario)->test(FacturaShow::class, ['factura' => $factura])
            ->assertDontSee('Editar / Reabrir borrador')
            ->call('reabrirBorrador')
            ->assertForbidden();

        $factura->refresh();
        $this->assertSame($estado, $factura->estado);
        $this->assertSame($mensajes, $factura->mensajes_error);
    }

    public static function estadosNoEditables(): array
    {
        $casos = [];
        foreach (['autorizada', 'enviada', 'pendiente_anular', 'anulada'] as $estado) {
            foreach (['facturador', 'admin'] as $rol) {
                $casos["{$estado} {$rol}"] = [$estado, $rol];
            }
        }

        return $casos;
    }

    #[Test]
    #[DataProvider('usuariosNoPermitidos')]
    public function usuarios_sin_permiso_no_pueden_editar_ni_reabrir_una_devuelta(string $rol, bool $mismaEmpresa, bool $esCreador): void
    {
        $creador = User::factory()->create();
        $usuario = $esCreador ? $creador : User::factory()->create(
            $mismaEmpresa ? ['empresa_id' => $creador->empresa_id] : [],
        );
        if ($rol !== '') {
            $usuario->assignRole($rol);
        }
        $mensajes = [['mensaje' => 'Error original']];
        $factura = Factura::factory()->create([
            'empresa_id' => $creador->empresa_id,
            'user_id' => $creador->id,
            'estado' => Factura::ESTADO_DEVUELTA,
            'mensajes_error' => $mensajes,
        ]);

        $this->assertFalse($usuario->can('update', $factura));
        $this->assertFalse($usuario->can('reabrirBorrador', $factura));
        $this->actingAs($usuario)->get(route('facturas.edit', $factura))->assertForbidden();
        $detalle = Livewire::actingAs($usuario)->test(FacturaShow::class, ['factura' => $factura]);
        if ($usuario->can('view', $factura)) {
            $detalle->assertDontSee('Editar / Reabrir borrador')->call('reabrirBorrador')->assertForbidden();
        } else {
            $detalle->assertForbidden();
        }

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_DEVUELTA, $factura->estado);
        $this->assertSame($mensajes, $factura->mensajes_error);
    }

    public static function usuariosNoPermitidos(): array
    {
        return [
            'facturador de otra empresa' => ['facturador', false, false],
            'admin de otra empresa' => ['admin', false, false],
            'facturador no creador' => ['facturador', true, false],
            'contador creador' => ['contador', true, true],
            'creador sin rol' => ['', true, true],
        ];
    }

    #[Test]
    public function guardar_cambios_en_una_devuelta_la_deja_en_borrador_y_limpia_errores(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('facturador');
        $factura = Factura::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_DEVUELTA,
            'correo_comprador' => 'comprador@example.com',
            'mensajes_error' => [['mensaje' => 'Error en recepción']],
        ]);
        $factura->detalles()->create([
            'codigo_principal' => 'P-1',
            'descripcion' => 'Producto original',
            'cantidad' => 1,
            'precio_unitario' => 10,
            'precio_sin_subsidio' => 10,
            'descuento' => 0,
            'precio_total_sin_impuesto' => 10,
            'codigo_impuesto' => 2,
            'codigo_porcentaje' => '4',
            'tarifa' => 15,
            'base_imponible' => 10,
            'valor_impuesto' => 1.5,
        ]);

        $this->actingAs($usuario)->get(route('facturas.index'))->assertDontSee(route('facturas.edit', $factura))->assertSee('Reabrir y corregir');
        Livewire::actingAs($usuario)->test(FacturaForm::class, ['factura' => $factura])
            ->set('razon_social_comprador', 'Comprador corregido')
            ->set('lineas.0.descripcion', 'Producto corregido')
            ->set('lineas.0.cantidad', '2')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('facturas.show', $factura));

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->estado);
        $this->assertNull($factura->mensajes_error);
        $this->assertSame('Comprador corregido', $factura->razon_social_comprador);
        $this->assertSame('23.00', $factura->importe_total);
        $this->assertCount(1, $factura->detalles);
        $this->assertSame('Producto corregido', $factura->detalles->first()->descripcion);
        $this->assertTrue($usuario->can('confirmar', $factura));
    }

    #[Test]
    public function una_devuelta_debe_pasar_a_borrador_antes_de_confirmarse_o_anularse_como_borrador(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('facturador');
        $factura = Factura::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_DEVUELTA,
        ]);

        $this->assertTrue($usuario->can('update', $factura));
        $this->assertFalse($usuario->can('confirmar', $factura));
        $this->assertFalse($usuario->can('anularBorrador', $factura));
        $this->assertFalse($usuario->can('delete', $factura));
        $factura->update(['estado' => Factura::ESTADO_BORRADOR]);
        $this->assertFalse($usuario->can('reabrirBorrador', $factura));
        $this->assertTrue($usuario->can('confirmar', $factura));
        $this->assertTrue($usuario->can('anularBorrador', $factura));
    }

    #[Test]
    public function la_vista_muestra_informacion_adicional_y_escapa_los_mensajes_del_sri(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('facturador');
        $factura = Factura::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_DEVUELTA,
            'mensajes_error' => [
                ['mensaje' => 'XML inválido', 'informacionAdicional' => '<script>alert("SRI")</script>'],
                ['mensaje' => 'Mensaje sin información adicional'],
                ['identificador' => '35'],
            ],
        ]);

        $this->actingAs($usuario)->get(route('facturas.show', $factura))
            ->assertOk()
            ->assertSee('XML inválido')
            ->assertSee('<script>alert("SRI")</script>')
            ->assertDontSee('<script>alert("SRI")</script>', false)
            ->assertSee('Mensaje sin información adicional')
            ->assertSee('{"identificador":"35"}');
    }
}
