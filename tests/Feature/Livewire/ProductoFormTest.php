<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Productos\ProductoForm;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductoFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_admin_puede_crear_un_producto_en_su_catalogo(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ProductoForm::class)
            ->set('codigo_principal', 'MESA-01')
            ->set('descripcion', 'Mesa de centro')
            ->set('precio_unitario', '120.00')
            ->set('codigo_porcentaje', '4')
            ->call('guardar')
            ->assertHasNoErrors();

        $producto = Producto::where('codigo_principal', 'MESA-01')->first();

        $this->assertNotNull($producto);
        $this->assertSame($empresa->id, $producto->empresa_id);
    }

    #[Test]
    public function no_se_puede_repetir_un_codigo_dentro_de_la_misma_empresa(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        Producto::factory()->for($empresa)->create(['codigo_principal' => 'MESA-01']);

        Livewire::actingAs($admin)
            ->test(ProductoForm::class)
            ->set('codigo_principal', 'MESA-01')
            ->set('descripcion', 'Otra mesa')
            ->set('precio_unitario', '80')
            ->set('codigo_porcentaje', '4')
            ->call('guardar')
            ->assertHasErrors(['codigo_principal']);
    }

    #[Test]
    public function el_mismo_codigo_si_se_permite_en_empresas_distintas(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $otraEmpresa = Empresa::factory()->create();
        Producto::factory()->for($otraEmpresa)->create(['codigo_principal' => 'MESA-01']);

        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ProductoForm::class)
            ->set('codigo_principal', 'MESA-01')
            ->set('descripcion', 'Mesa de centro')
            ->set('precio_unitario', '120')
            ->set('codigo_porcentaje', '4')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(2, Producto::where('codigo_principal', 'MESA-01')->count());
    }
}
