<?php

namespace Tests\Feature\Livewire\ReglasFacturacion;

use App\Livewire\ReglasFacturacion\ReglaFacturacionForm;
use App\Models\Producto;
use App\Models\ReglaFacturacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaReglaFacturacion;
use Tests\TestCase;

class ReglaFacturacionFormTest extends TestCase
{
    use CreaReglaFacturacion, RefreshDatabase;

    public function test_valida_y_guarda_productos_clientes_y_totales(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $user = User::find($regla->user_id);
        $user->assignRole('contador');
        $producto = Producto::where('empresa_id', $user->empresa_id)->first();
        Livewire::actingAs($user)->test(ReglaFacturacionForm::class)
            ->call('guardar')->assertHasErrors(['nombre', 'productos', 'clientes'])
            ->set('nombre', 'Nueva regla')->set('fecha_inicio', '2026-11-01')->set('dia_corte', 5)
            ->call('agregarProducto', $producto->id)
            ->set('productos.0.cantidad', '2')->set('productos.0.precio_unitario', '10')->set('productos.0.descuento', '1')
            ->assertSet('totales.importeTotal', '21.85')
            ->set('clientes', [['tipo_identificacion' => '06', 'identificacion' => 'XYZ', 'razon_social' => 'Cliente', 'direccion' => 'Quito', 'correo' => 'test@example.com', 'activo' => true]])
            ->call('guardar')->assertHasNoErrors()->assertRedirect();
        $nueva = ReglaFacturacion::where('nombre', 'Nueva regla')->sole();
        $this->assertSame($user->empresa_id, $nueva->empresa_id);
        $this->assertSame('2026-11-05', $nueva->proxima_ejecucion_at->toDateString());
        $this->assertSame(1, $nueva->productos()->count());
        $this->assertSame('XYZ', $nueva->clientes()->sole()->identificacion);
    }

    public function test_no_admite_productos_ajenos_ni_edicion_por_facturador(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $user = User::find($regla->user_id);
        $user->assignRole('admin');
        Livewire::actingAs($user)->test(ReglaFacturacionForm::class, ['regla' => $regla])
            ->set('productos.0.producto_id', Producto::factory()->create()->id)
            ->call('guardar')->assertHasErrors(['productos.0.producto_id']);
        $user->syncRoles(['facturador']);
        Livewire::actingAs($user)->test(ReglaFacturacionForm::class, ['regla' => $regla])->assertForbidden();
    }
}
