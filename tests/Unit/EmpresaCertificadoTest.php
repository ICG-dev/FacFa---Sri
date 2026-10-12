<?php

namespace Tests\Unit;

use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaCertificadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    #[Test]
    public function extrae_la_fecha_exacta_del_x509_con_la_clave_desencriptada(): void
    {
        [$p12, $timestamp] = $this->certificado('secreto123');
        Storage::disk('local')->put('certificados/firma.p12', $p12);
        $empresa = new Empresa([
            'certificado_path' => 'certificados/firma.p12',
            'certificado_password' => 'secreto123',
        ]);
        $this->assertNotSame('secreto123', $empresa->getAttributes()['certificado_password']);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $fecha = $empresa->fechaExpiracionCertificado();

        $this->assertInstanceOf(Carbon::class, $fecha);
        $this->assertSame($timestamp, $fecha->getTimestamp());
    }

    #[Test]
    #[DataProvider('certificadosAusentes')]
    public function retorna_null_si_el_certificado_no_esta_disponible(?string $path): void
    {
        $empresa = new Empresa(['certificado_path' => $path]);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $this->assertNull($empresa->fechaExpiracionCertificado());
    }

    public static function certificadosAusentes(): array
    {
        return [
            'sin ruta' => [null],
            'ruta vacía' => [''],
            'archivo inexistente' => ['certificados/ausente.p12'],
        ];
    }

    #[Test]
    public function retorna_null_si_el_archivo_no_es_un_pkcs12(): void
    {
        Storage::disk('local')->put('certificados/roto.p12', 'archivo dañado');
        $empresa = new Empresa(['certificado_path' => 'certificados/roto.p12']);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $this->assertNull($empresa->fechaExpiracionCertificado());
    }

    #[Test]
    public function retorna_null_si_la_clave_del_certificado_es_incorrecta(): void
    {
        [$p12] = $this->certificado('correcta');
        Storage::disk('local')->put('certificados/firma.p12', $p12);
        $empresa = new Empresa([
            'certificado_path' => 'certificados/firma.p12',
            'certificado_password' => 'incorrecta',
        ]);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $this->assertNull($empresa->fechaExpiracionCertificado());
    }

    #[Test]
    public function permite_un_certificado_sin_password(): void
    {
        [$p12, $timestamp] = $this->certificado('');
        Storage::disk('local')->put('certificados/firma.p12', $p12);
        $empresa = new Empresa(['certificado_path' => 'certificados/firma.p12']);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $this->assertSame($timestamp, $empresa->fechaExpiracionCertificado()?->getTimestamp());
    }

    #[Test]
    public function retorna_null_si_la_clave_guardada_no_se_puede_desencriptar(): void
    {
        [$p12] = $this->certificado('secreto123');
        Storage::disk('local')->put('certificados/firma.p12', $p12);
        $empresa = new Empresa;
        $empresa->setRawAttributes([
            'certificado_path' => 'certificados/firma.p12',
            'certificado_password' => 'valor-encriptado-invalido',
        ]);
        $this->assertTrue(method_exists($empresa, 'fechaExpiracionCertificado'));

        $this->assertNull($empresa->fechaExpiracionCertificado());
    }

    private function certificado(string $password): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['CN' => 'EMPRESA TEST'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 30);
        $this->assertTrue(openssl_pkcs12_export($cert, $p12, $key, $password));

        return [$p12, openssl_x509_parse($cert)['validTo_time_t']];
    }
}
