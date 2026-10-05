<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaFormBuscadorProductosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function escribir_un_codigo_muestra_sugerencias_del_catalogo_de_la_propia_empresa(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        $producto = Producto::factory()->for($empresa)->create([
            'codigo_principal' => 'SILLA-01',
            'descripcion' => 'Silla de madera',
            'precio_unitario' => 45.50,
            'tarifa' => 15,
        ]);

        
        Producto::factory()->create(['codigo_principal' => 'SILLA-02']);

        $component = Livewire::actingAs($usuario)
            ->test(FacturaForm::class)
            ->set('lineas.0.codigo_principal', 'SILLA');

        $sugerencias = $component->get('sugerenciasProductos')[0];

        $this->assertCount(1, $sugerencias);
        $this->assertSame($producto->id, $sugerencias->first()->id);
    }

    #[Test]
    public function seleccionar_una_sugerencia_autocompleta_descripcion_precio_e_iva(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        $producto = Producto::factory()->for($empresa)->create([
            'codigo_principal' => 'SILLA-01',
            'descripcion' => 'Silla de madera',
            'precio_unitario' => 45.50,
            'tarifa' => 0,
        ]);

        Livewire::actingAs($usuario)
            ->test(FacturaForm::class)
            ->set('lineas.0.codigo_principal', 'SILLA')
            ->call('seleccionarProducto', 0, $producto->id)
            ->assertSet('lineas.0.codigo_principal', 'SILLA-01')
            ->assertSet('lineas.0.descripcion', 'Silla de madera')
            ->assertSet('lineas.0.precio_unitario', 45.50)
            ->assertSet('lineas.0.tarifa', 0.0)
            ->assertSet('sugerenciasProductos', []);
    }

    #[Test]
    public function los_comodines_del_usuario_se_buscan_literalmente(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $usuario->assignRole('facturador');
        $literal = Producto::factory()->for($empresa)->create(['codigo_principal' => 'A_%', 'descripcion' => 'Literal']);
        Producto::factory()->for($empresa)->create(['codigo_principal' => 'ABC', 'descripcion' => 'Otro']);

        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)->set('lineas.0.codigo_principal', 'A_');
        $this->assertSame([$literal->id], $form->get('sugerenciasProductos')[0]->pluck('id')->all());
        $form->set('lineas.0.codigo_principal', '_%');
        $this->assertSame([$literal->id], $form->get('sugerenciasProductos')[0]->pluck('id')->all());
    }
}
