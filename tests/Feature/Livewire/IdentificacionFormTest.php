<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Empresas\EmpresaForm;
use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdentificacionFormTest extends TestCase
{
    use RefreshDatabase;

    public static function compradores(): array
    {
        return [
            ['05', '0100000009', true], ['05', '3000000004', true],
            ['05', '0100000008', false], ['05', '2500000001', false],
            ['04', '0100000009002', true], ['04', '0190000000002', true],
            ['04', '0160000090002', true], ['04', '0100000008001', false],
            ['04', '0190000000000', false], ['04', '0180000000001', false],
            ['06', 'P', true], ['06', 'PAS-TEST/#', true], ['08', 'X', true],
            ['08', 'EXTERIOR-TEST/#', true],
        ];
    }

    #[Test]
    #[DataProvider('compradores')]
    public function aplica_la_regla_segun_tipo_de_identificacion_del_comprador(string $tipo, string $id, bool $valido): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)
            ->set('tipo_identificacion_comprador', $tipo)->set('identificacion_comprador', $id)
            ->set('razon_social_comprador', 'Comprador sintético')->set('correo_comprador', 'test@example.com')
            ->set('lineas.0.codigo_principal', 'TEST')->set('lineas.0.descripcion', 'Sintético')
            ->set('lineas.0.precio_unitario', '10.00')->call('guardar');

        if ($valido) {
            $form->assertHasNoErrors();
            $this->assertSame($id, Factura::sole()->identificacion_comprador);
        } else {
            $form->assertHasErrors(['identificacion_comprador']);
            $this->assertDatabaseCount('facturas', 0);
        }
    }

    public static function empresas(): array
    {
        return [['0100000009002', true], ['0190000000002', true], ['0160000090002', true],
            ['0100000008001', false], ['0190000000000', false], ['2590000000001', false]];
    }

    #[Test]
    #[DataProvider('empresas')]
    public function empresa_exige_un_ruc_valido_sin_modulo_once_para_sociedades(string $ruc, bool $valido): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->create(['empresa_id' => null]);
        $usuario->assignRole('super_admin');
        $form = Livewire::actingAs($usuario)->test(EmpresaForm::class)
            ->set('ruc', $ruc)->set('razon_social', 'Empresa sintética')->set('dir_matriz', 'Dirección de prueba')
            ->call('guardar');
        if ($valido) {
            $form->assertHasNoErrors();
            $this->assertSame($ruc, Empresa::sole()->ruc);
        } else {
            $form->assertHasErrors(['ruc']);
            $this->assertDatabaseCount('empresas', 0);
        }
    }
}
