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

class FacturaIndexReabrirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    #[DataProvider('facturasReabribles')]
    public function reabre_la_factura_limpia_errores_y_redirige_a_edicion(string $estado, string $rol, bool $esCreador): void
    {
        $creador = User::factory()->create();
        $usuario = $esCreador ? $creador : User::factory()->create(['empresa_id' => $creador->empresa_id]);
        $usuario->assignRole($rol);
        $factura = Factura::factory()->create([
            'empresa_id' => $creador->empresa_id,
            'user_id' => $creador->id,
            'estado' => $estado,
            'mensajes_error' => [['mensaje' => 'Error original']],
            'secuencial' => 77,
            'clave_acceso' => str_repeat('7', 49),
        ]);

        Livewire::actingAs($usuario)->test(FacturaIndex::class)
            ->assertSee('Reabrir y corregir')
            ->call('reabrirBorrador', $factura->id)
            ->assertRedirect(route('facturas.edit', $factura));

        $factura->refresh();
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->estado);
        $this->assertNull($factura->mensajes_error);
        $this->assertSame(77, $factura->secuencial);
        $this->assertSame(str_repeat('7', 49), $factura->clave_acceso);
        $this->actingAs($usuario)->get(route('facturas.edit', $factura))->assertOk();
    }

    public static function facturasReabribles(): array
    {
        return [
            ['rechazada', 'facturador', true],
            ['devuelta', 'facturador', true],
            ['rechazada', 'admin', false],
            ['devuelta', 'admin', false],
        ];
    }

    #[Test]
    #[DataProvider('casosSinPermiso')]
    public function no_permite_reabrir_sin_permiso_y_conserva_la_factura(string $estado, string $rol, bool $esCreador): void
    {
        $creador = User::factory()->create();
        $usuario = $esCreador ? $creador : User::factory()->create(['empresa_id' => $creador->empresa_id]);
        $usuario->assignRole($rol);
        $mensajes = [['mensaje' => 'Error original']];
        $factura = Factura::factory()->create([
            'empresa_id' => $creador->empresa_id,
            'user_id' => $creador->id,
            'estado' => $estado,
            'mensajes_error' => $mensajes,
        ]);

        Livewire::actingAs($usuario)->test(FacturaIndex::class)
            ->assertDontSee('Reabrir y corregir')
            ->call('reabrirBorrador', $factura->id)
            ->assertForbidden();

        $factura->refresh();
        $this->assertSame($estado, $factura->estado);
        $this->assertSame($mensajes, $factura->mensajes_error);
    }

    public static function casosSinPermiso(): array
    {
        return [
            ['rechazada', 'facturador', false],
            ['rechazada', 'contador', true],
            ['devuelta', 'contador', true],
            ['borrador', 'admin', true],
            ['enviada', 'admin', true],
            ['autorizada', 'admin', true],
            ['pendiente_anular', 'admin', true],
            ['anulada', 'admin', true],
        ];
    }

    #[Test]
    public function no_permite_reabrir_facturas_de_otra_empresa(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('admin');
        $factura = Factura::factory()->create([
            'estado' => Factura::ESTADO_RECHAZADA,
            'mensajes_error' => [['mensaje' => 'Error original']],
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        try {
            Livewire::actingAs($usuario)->test(FacturaIndex::class)
                ->call('reabrirBorrador', $factura->id);
        } finally {
            $this->assertSame(Factura::ESTADO_RECHAZADA, $factura->fresh()->estado);
            $this->assertSame([['mensaje' => 'Error original']], $factura->fresh()->mensajes_error);
        }
    }
}
