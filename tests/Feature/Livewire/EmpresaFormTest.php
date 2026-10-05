<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Empresas\EmpresaForm;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_super_admin_puede_crear_una_empresa_con_su_certificado(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(EmpresaForm::class)
            ->set('ruc', '1792146739001')
            ->set('razon_social', 'NUEVA EMPRESA S.A.')
            ->set('dir_matriz', 'Av. Principal 123')
            ->set('establecimiento', '001')
            ->set('punto_emision', '001')
            ->set('ambiente', 1)
            ->set('certificado', $this->certificadoValido('clave-secreta'))
            ->set('certificado_password', 'clave-secreta')
            ->call('guardar')
            ->assertHasNoErrors();

        $empresa = Empresa::where('ruc', '1792146739001')->first();

        $this->assertNotNull($empresa);
        $this->assertNotNull($empresa->certificado_path);
        Storage::disk('local')->assertExists($empresa->certificado_path);
        $this->assertSame('clave-secreta', $empresa->certificado_password);
    }

    #[Test]
    public function el_super_admin_puede_crear_una_empresa_sin_certificado(): void
    {
        
        
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(EmpresaForm::class)
            ->set('ruc', '1792146739001')
            ->set('razon_social', 'NUEVA EMPRESA S.A.')
            ->set('dir_matriz', 'Av. Principal 123')
            ->call('guardar')
            ->assertHasNoErrors();

        $empresa = Empresa::where('ruc', '1792146739001')->first();

        $this->assertNotNull($empresa);
        $this->assertNull($empresa->certificado_path);
    }

    #[Test]
    public function el_admin_de_la_empresa_puede_subir_el_certificado_despues_desde_mi_empresa(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $empresa = Empresa::factory()->create(['certificado_path' => null, 'certificado_password' => null]);
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(EmpresaForm::class, ['empresa' => $empresa])
            ->set('certificado', $this->certificadoValido('clave-de-la-empresa'))
            ->set('certificado_password', 'clave-de-la-empresa')
            ->call('guardar')
            ->assertHasNoErrors();

        $empresa->refresh();

        $this->assertNotNull($empresa->certificado_path);
        Storage::disk('local')->assertExists($empresa->certificado_path);
        $this->assertSame('clave-de-la-empresa', $empresa->certificado_password);
    }

    #[Test]
    public function un_certificado_invalido_conserva_el_anterior_y_los_datos(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Storage::fake('local');
        Storage::disk('local')->put('certificados/anterior.p12', 'anterior');
        $empresa = Empresa::factory()->create(['certificado_path' => 'certificados/anterior.p12']);
        $admin = User::factory()->for($empresa)->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(EmpresaForm::class, ['empresa' => $empresa])
            ->set('razon_social', 'No guardar')
            ->set('certificado', UploadedFile::fake()->create('invalido.p12', 10))
            ->set('certificado_password', 'incorrecta')
            ->call('guardar')->assertHasErrors(['certificado']);

        $this->assertSame($empresa->razon_social, $empresa->fresh()->razon_social);
        $this->assertSame('certificados/anterior.p12', $empresa->fresh()->certificado_path);
        $this->assertSame('anterior', Storage::disk('local')->get('certificados/anterior.p12'));
    }

    #[Test]
    public function admin_no_puede_cambiar_datos_fiscales_protegidos(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create(['ambiente' => 1]);
        $admin = User::factory()->for($empresa)->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(EmpresaForm::class, ['empresa' => $empresa])
            ->set('ambiente', 2)->set('ruc', '1234567890001')
            ->set('establecimiento', '999')->set('punto_emision', '999')
            ->set('nombre_comercial', 'Nuevo nombre')
            ->call('guardar')->assertHasNoErrors();

        $actual = $empresa->fresh();
        foreach (['ambiente', 'ruc', 'establecimiento', 'punto_emision'] as $campo) {
            $this->assertSame($empresa->{$campo}, $actual->{$campo});
        }
        $this->assertSame('Nuevo nombre', $actual->nombre_comercial);
    }

    #[Test]
    public function ruc_duplicado_da_error_de_validacion(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Livewire::actingAs($admin)->test(EmpresaForm::class)
            ->set('ruc', $empresa->ruc)->set('razon_social', 'Duplicada')->set('dir_matriz', 'Dirección')
            ->call('guardar')->assertHasErrors(['ruc' => 'unique']);
    }

    #[Test]
    public function un_fallo_de_bd_al_reemplazar_el_certificado_conserva_el_archivo_anterior(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('certificados/anterior.p12', 'anterior');
        $empresa = Empresa::factory()->create(['certificado_path' => 'certificados/anterior.p12']);
        $certificado = $this->certificadoValido('clave');
        Empresa::saving(function () {
            throw new \RuntimeException('Fallo de BD simulado');
        });

        try {
            app(\App\Actions\Empresas\GuardarEmpresaAction::class)->ejecutar($empresa, [], $certificado, 'clave');
            $this->fail('Se esperaba un fallo de guardado.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo de BD simulado', $e->getMessage());
            $this->assertSame('certificados/anterior.p12', $empresa->fresh()->certificado_path);
            $this->assertSame(['certificados/anterior.p12'], Storage::disk('local')->files('certificados'));
        } finally {
            Empresa::flushEventListeners();
        }
    }

    private function certificadoValido(string $password): UploadedFile
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['CN' => 'PRUEBAS'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 1);
        openssl_pkcs12_export($cert, $p12, $key, $password);

        return UploadedFile::fake()->createWithContent('firma.p12', $p12);
    }
}
