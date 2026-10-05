<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function solo_el_super_admin_puede_ver_el_listado_y_crear_empresas(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($admin->can('viewAny', Empresa::class));
        $this->assertFalse($admin->can('create', Empresa::class));

        $this->assertTrue($superAdmin->can('viewAny', Empresa::class));
        $this->assertTrue($superAdmin->can('create', Empresa::class));

        $this->actingAs($admin)->get(route('empresas.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('empresas.create'))->assertForbidden();
    }

    #[Test]
    public function el_admin_de_una_empresa_no_puede_editar_otra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $adminA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $adminA->assignRole('admin');

        $this->assertTrue($adminA->can('update', $empresaA));
        $this->assertFalse($adminA->can('update', $empresaB));

        $this->actingAs($adminA)->get(route('empresas.edit', $empresaB))->assertForbidden();
    }

    #[Test]
    public function el_admin_de_su_propia_empresa_si_puede_editarla(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('empresas.edit', $empresa))->assertOk();
    }
}
