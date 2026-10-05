<?php

namespace Tests\Feature\Livewire;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;






class FacturaPaginasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_listado_de_facturas_renderiza_para_un_facturador(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        Factura::factory()->for($empresa)->create(['user_id' => $usuario->id]);

        $this->actingAs($usuario)
            ->get(route('facturas.index'))
            ->assertOk()
            ->assertSee('Facturas');
    }

    #[Test]
    public function el_formulario_de_creacion_renderiza_para_un_facturador(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        $this->actingAs($usuario)
            ->get(route('facturas.create'))
            ->assertOk()
            ->assertSee('Nueva factura');
    }

    #[Test]
    public function el_detalle_de_factura_renderiza_para_un_usuario_de_la_misma_empresa(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        $factura = Factura::factory()->for($empresa)->create(['user_id' => $usuario->id]);
        $factura->detalles()->create([
            'codigo_principal' => 'P-1',
            'descripcion' => 'Producto de prueba',
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

        $this->actingAs($usuario)
            ->get(route('facturas.show', $factura))
            ->assertOk()
            ->assertSee($factura->razon_social_comprador);
    }
}
