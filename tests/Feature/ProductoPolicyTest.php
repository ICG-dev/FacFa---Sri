<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductoPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function un_facturador_puede_ver_el_catalogo_pero_no_administrarlo(): void
    {
        $empresa = Empresa::factory()->create();
        $facturador = User::factory()->create(['empresa_id' => $empresa->id]);
        $facturador->assignRole('facturador');

        $producto = Producto::factory()->for($empresa)->create();

        $this->assertTrue($facturador->can('viewAny', Producto::class));
        $this->assertFalse($facturador->can('create', Producto::class));
        $this->assertFalse($facturador->can('update', $producto));
        $this->assertFalse($facturador->can('delete', $producto));

        $this->actingAs($facturador)->get(route('productos.create'))->assertForbidden();
    }

    #[Test]
    public function el_admin_de_la_empresa_puede_administrar_su_catalogo(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $producto = Producto::factory()->for($empresa)->create();

        $this->assertTrue($admin->can('create', Producto::class));
        $this->assertTrue($admin->can('update', $producto));
        $this->assertTrue($admin->can('delete', $producto));
    }

    #[Test]
    public function un_admin_no_puede_administrar_productos_de_otra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $adminA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $adminA->assignRole('admin');

        $productoB = Producto::factory()->for($empresaB)->create();

        $this->assertFalse($adminA->can('update', $productoB));
        $this->assertFalse($adminA->can('delete', $productoB));

        $this->actingAs($adminA)->get(route('productos.edit', $productoB))->assertForbidden();
    }
}
