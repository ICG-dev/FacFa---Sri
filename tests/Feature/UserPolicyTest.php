<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function un_admin_no_puede_ver_ni_editar_usuarios_de_otra_empresa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $adminA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $adminA->assignRole('admin');

        $usuarioB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $usuarioB->assignRole('facturador');

        $this->assertFalse($adminA->can('view', $usuarioB));
        $this->assertFalse($adminA->can('update', $usuarioB));
        $this->assertFalse($adminA->can('delete', $usuarioB));

        $this->actingAs($adminA)->get(route('usuarios.edit', $usuarioB))->assertForbidden();
    }

    #[Test]
    public function un_admin_no_puede_asignar_el_rol_super_admin(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $nuevoUsuario = User::factory()->make(['empresa_id' => $empresa->id]);

        $this->assertFalse($admin->can('asignarRol', [$nuevoUsuario, 'super_admin']));
        $this->assertTrue($admin->can('asignarRol', [$nuevoUsuario, 'facturador']));
    }

    #[Test]
    public function un_admin_no_puede_crear_un_usuario_en_otra_empresa_ni_asignarle_super_admin_manipulando_la_peticion(): void
    {
        $empresaPropia = Empresa::factory()->create();
        $empresaAjena = Empresa::factory()->create();

        $admin = User::factory()->create(['empresa_id' => $empresaPropia->id]);
        $admin->assignRole('admin');

        
        
        
        
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Usuarios\UsuarioForm::class)
            ->set('name', 'Atacante')
            ->set('email', 'atacante@example.com')
            ->set('password', 'password123')
            ->set('empresa_id', $empresaAjena->id)
            ->set('rol', 'super_admin')
            ->call('guardar')
            ->assertHasErrors(['rol']);

        $this->assertDatabaseMissing('users', ['email' => 'atacante@example.com']);
    }

    #[Test]
    public function un_admin_si_puede_crear_un_usuario_valido_en_su_propia_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Usuarios\UsuarioForm::class)
            ->set('name', 'Nuevo Facturador')
            ->set('email', 'nuevo@example.com')
            ->set('password', 'password123')
            ->set('rol', 'facturador')
            ->call('guardar')
            ->assertHasNoErrors();

        $creado = User::where('email', 'nuevo@example.com')->first();

        $this->assertNotNull($creado);
        $this->assertSame($empresa->id, $creado->empresa_id);
        $this->assertTrue($creado->hasRole('facturador'));
    }

    #[Test]
    public function el_super_admin_si_puede_crear_usuarios_super_admin_sin_empresa(): void
    {
        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        \Livewire\Livewire::actingAs($superAdmin)
            ->test(\App\Livewire\Usuarios\UsuarioForm::class)
            ->set('name', 'Otro Super Admin')
            ->set('email', 'otro-super@example.com')
            ->set('password', 'password123')
            ->set('rol', 'super_admin')
            ->call('guardar')
            ->assertHasNoErrors();

        $creado = User::where('email', 'otro-super@example.com')->first();

        $this->assertNotNull($creado);
        $this->assertNull($creado->empresa_id);
        $this->assertTrue($creado->hasRole('super_admin'));
    }
}
