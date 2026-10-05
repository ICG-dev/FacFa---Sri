<?php

namespace Tests\Feature\Sri;

use App\Actions\Sri\ConfirmarFacturaAction;
use App\Jobs\EnviarComprobanteRecepcionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmarFacturaActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function confirmar_asigna_secuencial_clave_de_acceso_firma_el_xml_y_despacha_el_envio(): void
    {
        Bus::fake([EnviarComprobanteRecepcionJob::class]);
        Storage::fake('local');

        [$certPem, $keyPem, $password] = $this->generarCertificadoDePrueba();
        Storage::disk('local')->put('certificados/empresa-test.p12', $this->empaquetarP12($certPem, $keyPem, $password));

        $empresa = Empresa::factory()->create([
            'ultimo_secuencial' => 41,
            'certificado_path' => 'certificados/empresa-test.p12',
            'certificado_password' => $password,
        ]);

        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);

        $factura = Factura::factory()->for($empresa)->create([
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_BORRADOR,
        ]);

        FacturaDetalle::factory()->for($factura)->create();

        (new ConfirmarFacturaAction(
            app(\App\Services\Sri\ClaveAccesoService::class),
            app(\App\Services\Sri\FacturaXmlBuilder::class),
            app(\App\Services\Sri\XadesSigner::class),
        ))->ejecutar($factura);

        $factura->refresh();
        $empresa->refresh();

        $this->assertSame(Factura::ESTADO_ENVIADA, $factura->estado);
        $this->assertSame($empresa->ambiente, $factura->ambiente);
        $this->assertSame((string) $factura->ambiente, substr($factura->clave_acceso, 23, 1));
        $this->assertSame(42, $factura->secuencial);
        $this->assertSame(42, $empresa->ultimo_secuencial);
        $this->assertSame('001', $factura->establecimiento);
        $this->assertNotNull($factura->clave_acceso);
        $this->assertSame(49, strlen($factura->clave_acceso));
        $this->assertNotNull($factura->xml_generado);
        $this->assertStringContainsString('ds:Signature', $factura->xml_firmado);
        $this->assertNotNull($factura->enviado_at);

        Bus::assertDispatched(EnviarComprobanteRecepcionJob::class, fn ($job) => $job->factura->is($factura));
    }

    #[Test]
    public function confirmar_dos_veces_un_modelo_desactualizado_no_duplica_el_envio(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $empresa = Empresa::factory()->create(['ultimo_secuencial' => 41]);
        $factura = Factura::factory()->for($empresa)->create(['estado' => Factura::ESTADO_BORRADOR]);
        FacturaDetalle::factory()->for($factura)->create();
        $this->mock(\App\Services\Sri\XadesSigner::class)
            ->shouldReceive('firmar')->once()->andReturn('<firmado/>');
        $accion = app(ConfirmarFacturaAction::class);
        $accion->ejecutar($factura);

        try {
            $accion->ejecutar($factura);
            $this->fail('Se permitió confirmar una factura ya enviada.');
        } catch (\DomainException $e) {
            $this->assertSame(42, $empresa->fresh()->ultimo_secuencial);
            \Illuminate\Support\Facades\Queue::assertPushed(EnviarComprobanteRecepcionJob::class, 1);
        }
    }

    


    private function generarCertificadoDePrueba(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new(['CN' => 'PRUEBAS SRI'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 365);

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem);

        return [$certPem, $keyPem, 'clave-prueba'];
    }

    private function empaquetarP12(string $certPem, string $keyPem, string $password): string
    {
        openssl_pkcs12_export($certPem, $p12, $keyPem, $password);

        return $p12;
    }
}
