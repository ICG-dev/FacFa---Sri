<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function usuario_de_la_misma_empresa_no_puede_editar_la_factura_de_otro_usuario(): void
    {
        $empresa = Empresa::factory()->create();

        $duenio = User::factory()->create(['empresa_id' => $empresa->id]);
        $duenio->assignRole('facturador');

        $otroUsuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $otroUsuario->assignRole('facturador');

        $factura = Factura::factory()->for($empresa)->for($duenio, 'usuario')->create([
            'user_id' => $duenio->id,
            'estado' => Factura::ESTADO_BORRADOR,
        ]);

        $this->assertFalse($otroUsuario->can('update', $factura));

        $response = $this->actingAs($otroUsuario)->get(route('facturas.edit', $factura));
        $response->assertForbidden();
    }

    #[Test]
    public function usuario_de_otra_empresa_no_puede_ver_ni_editar_la_factura(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();

        $duenio = User::factory()->create(['empresa_id' => $empresaA->id]);
        $duenio->assignRole('facturador');

        $usuarioDeOtraEmpresa = User::factory()->create(['empresa_id' => $empresaB->id]);
        $usuarioDeOtraEmpresa->assignRole('facturador');

        $factura = Factura::factory()->for($empresaA)->create([
            'user_id' => $duenio->id,
            'estado' => Factura::ESTADO_BORRADOR,
        ]);

        $this->assertFalse($usuarioDeOtraEmpresa->can('view', $factura));
        $this->assertFalse($usuarioDeOtraEmpresa->can('update', $factura));

        $response = $this->actingAs($usuarioDeOtraEmpresa)->get(route('facturas.edit', $factura));
        $response->assertForbidden();
    }

    #[Test]
    public function el_dueno_no_puede_editar_su_propia_factura_si_ya_no_esta_en_borrador(): void
    {
        $empresa = Empresa::factory()->create();
        $duenio = User::factory()->create(['empresa_id' => $empresa->id]);
        $duenio->assignRole('facturador');

        $factura = Factura::factory()->for($empresa)->create([
            'user_id' => $duenio->id,
            'estado' => Factura::ESTADO_ENVIADA,
        ]);

        $this->assertFalse($duenio->can('update', $factura));

        $response = $this->actingAs($duenio)->get(route('facturas.edit', $factura));
        $response->assertForbidden();
    }

    #[Test]
    public function anular_borrador_es_denegado_fuera_de_borrador_o_fuera_de_la_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $duenio = User::factory()->create(['empresa_id' => $empresa->id]);
        $duenio->assignRole('facturador');

        $facturaEnviada = Factura::factory()->for($empresa)->create([
            'user_id' => $duenio->id,
            'estado' => Factura::ESTADO_ENVIADA,
        ]);
        $this->assertFalse($duenio->can('anularBorrador', $facturaEnviada));

        $otraEmpresa = Empresa::factory()->create();
        $usuarioDeOtraEmpresa = User::factory()->create(['empresa_id' => $otraEmpresa->id]);
        $usuarioDeOtraEmpresa->assignRole('facturador');

        $facturaBorrador = Factura::factory()->for($empresa)->create([
            'user_id' => $duenio->id,
            'estado' => Factura::ESTADO_BORRADOR,
        ]);
        $this->assertFalse($usuarioDeOtraEmpresa->can('anularBorrador', $facturaBorrador));
        $this->assertTrue($duenio->can('anularBorrador', $facturaBorrador));
    }

    #[Test]
    public function solicitar_anulacion_es_denegado_a_roles_distintos_de_admin(): void
    {
        $empresa = Empresa::factory()->create();
        $facturador = User::factory()->create(['empresa_id' => $empresa->id]);
        $facturador->assignRole('facturador');

        $factura = Factura::factory()->for($empresa)->autorizada()->create([
            'user_id' => $facturador->id,
            'fecha_emision' => CarbonImmutable::now()->toDateString(),
        ]);

        $this->assertFalse($facturador->can('solicitarAnulacion', $factura));
    }

    #[Test]
    public function solicitar_anulacion_es_denegado_para_consumidor_final_incluso_para_admin_dentro_del_plazo(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $factura = Factura::factory()->for($empresa)->autorizada()->consumidorFinal()->create([
            'user_id' => $admin->id,
            'fecha_emision' => CarbonImmutable::now()->toDateString(),
        ]);

        $this->assertFalse($admin->can('solicitarAnulacion', $factura));
    }

    #[Test]
    public function solicitar_anulacion_es_permitido_para_admin_dentro_del_plazo_en_linea(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        $factura = Factura::factory()->for($empresa)->autorizada()->create([
            'user_id' => $admin->id,
            'fecha_emision' => CarbonImmutable::now()->toDateString(),
        ]);

        $this->assertTrue($admin->can('solicitarAnulacion', $factura));
    }
}
