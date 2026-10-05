<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function un_usuario_de_empresa_ve_sus_propias_facturas_enviadas(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        Factura::factory()->for($empresa)->create([
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_ENVIADA,
            'razon_social_comprador' => 'CLIENTE DE PRUEBA',
            'enviado_at' => now(),
        ]);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Enviadas al SRI')
            ->assertSee('CLIENTE DE PRUEBA');
    }

    #[Test]
    public function el_super_admin_ve_estadisticas_de_la_plataforma_en_vez_de_facturas(): void
    {
        Empresa::factory()->count(2)->create();

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Empresas registradas')
            ->assertDontSee('Enviadas al SRI');
    }
}
