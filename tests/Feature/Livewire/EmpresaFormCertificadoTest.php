<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Empresas\EmpresaForm;
use App\Models\Empresa;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaFormCertificadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->for($this->empresa)->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function muestra_ayudas_contextuales_del_sri(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EmpresaForm::class, ['empresa' => $this->empresa])
            ->assertSee('Código de 3 dígitos asignado por el SRI (ej. 001).')
            ->assertSee('Código de 3 dígitos para el punto de facturación o caja (ej. 001).')
            ->assertSee('Número de resolución del SRI si aplica (dejar en blanco si no).');
    }

    #[Test]
    public function muestra_password_oculta_con_toggle_accesible_sin_exponer_la_clave_guardada(): void
    {
        $this->empresa->update(['certificado_password' => 'clave-privada-guardada']);

        $componente = Livewire::actingAs($this->admin)
            ->test(EmpresaForm::class, ['empresa' => $this->empresa])
            ->assertSet('certificado_password', '')
            ->assertDontSee('clave-privada-guardada');

        $dom = new \DOMDocument;
        @$dom->loadHTML('<meta charset="UTF-8">'.$componente->html());
        $xpath = new \DOMXPath($dom);
        $input = $xpath->query('//input[@id="certificado_password"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('password', $input->getAttribute('type'));
        $this->assertSame('new-password', $input->getAttribute('autocomplete'));
        $this->assertSame("verPassword ? 'text' : 'password'", $input->getAttribute(':type'));

        $contenedor = $xpath->query('//input[@id="certificado_password"]/ancestor::*[@x-data][1]')->item(0);
        $this->assertNotNull($contenedor);
        $this->assertSame('{ verPassword: false }', $contenedor->getAttribute('x-data'));
        $boton = $xpath->query('.//button', $contenedor)->item(0);
        $this->assertNotNull($boton);
        $this->assertSame('button', $boton->getAttribute('type'));
        $this->assertStringContainsString('@click="verPassword = !verPassword"', $componente->html());
        $this->assertSame("verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'", $boton->getAttribute(':aria-label'));
        $this->assertSame($boton->getAttribute(':aria-label'), $boton->getAttribute(':title'));
        $this->assertSame('verPassword', $boton->getAttribute(':aria-pressed'));
        $this->assertSame(2, $xpath->query('.//*[local-name()="svg"]', $boton)->length);
    }

    #[Test]
    public function muestra_fecha_de_vencimiento_del_certificado_vigente(): void
    {
        $vencimiento = $this->cargarCertificado();

        Livewire::actingAs($this->admin)
            ->test(EmpresaForm::class, ['empresa' => $this->empresa])
            ->assertSee('Ya hay un certificado cargado (vence el '.$vencimiento->format('d/m/Y').').')
            ->assertSee('Sube uno nuevo solo si quieres reemplazarlo.')
            ->assertDontSee('El certificado cargado venció');
    }

    #[Test]
    public function muestra_alerta_roja_si_el_certificado_esta_vencido(): void
    {
        $vencimiento = $this->cargarCertificado();
        $this->travelTo($vencimiento->copy()->addSecond());

        try {
            $componente = Livewire::actingAs($this->admin)
                ->test(EmpresaForm::class, ['empresa' => $this->empresa])
                ->assertSee('El certificado cargado venció el '.$vencimiento->format('d/m/Y').'.')
                ->assertSee('Sube un certificado vigente para poder emitir comprobantes.')
                ->assertDontSee('Ya hay un certificado cargado (vence el');

            $this->assertMatchesRegularExpression(
                '/<p\b[^>]*class="[^"]*text-destructive[^"]*"[^>]*>El certificado cargado venció el/u',
                $componente->html(),
            );
        } finally {
            $this->travelBack();
        }
    }

    private function cargarCertificado(): Carbon
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['CN' => 'EMPRESA TEST'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 30);
        $this->assertTrue(openssl_pkcs12_export($cert, $p12, $key, 'secreto123'));
        Storage::disk('local')->put('certificados/test.p12', $p12);
        $this->empresa->update([
            'certificado_path' => 'certificados/test.p12',
            'certificado_password' => 'secreto123',
        ]);

        return Carbon::createFromTimestamp(openssl_x509_parse($cert)['validTo_time_t']);
    }
}
