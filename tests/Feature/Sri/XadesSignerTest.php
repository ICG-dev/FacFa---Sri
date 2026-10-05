<?php

namespace Tests\Feature\Sri;

use App\Models\Empresa;
use App\Services\Sri\XadesSigner;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Tests\TestCase;

class XadesSignerTest extends TestCase
{
    #[Test]
    public function firma_un_xml_y_la_firma_es_criptograficamente_valida(): void
    {
        Storage::fake('local');

        [$certificadoPem, $llavePrivadaPem, $password] = $this->generarCertificadoDePrueba();

        Storage::disk('local')->put(
            'certificados/empresa-test.p12',
            $this->empaquetarP12($certificadoPem, $llavePrivadaPem, $password)
        );

        $empresa = new Empresa([
            'ruc' => '1792146739001',
            'razon_social' => 'EMPRESA DE PRUEBA S.A.',
            'dir_matriz' => 'Av. Siempre Viva 123',
            'ambiente' => 1,
            'certificado_path' => 'certificados/empresa-test.p12',
            'certificado_password' => $password,
        ]);

        $xmlSinFirmar = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<factura id="comprobante" version="1.0.0"><infoTributaria><ruc>1792146739001</ruc></infoTributaria></factura>';

        $xmlFirmado = (new XadesSigner)->firmar($xmlSinFirmar, $empresa);

        $doc = new \DOMDocument;
        $doc->loadXML($xmlFirmado);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $references = $xpath->query('//ds:Signature/ds:SignedInfo/ds:Reference');
        $this->assertSame(3, $references->length);

        $signedInfo = $xpath->query('//ds:Signature/ds:SignedInfo')->item(0);
        $signatureValueB64 = $xpath->query('//ds:Signature/ds:SignatureValue')->item(0)->textContent;

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'public']);
        $key->loadKey($certificadoPem, false, true);

        $canonicalSignedInfo = $signedInfo->C14N(false, false);
        $verificado = $key->verifySignature($canonicalSignedInfo, base64_decode($signatureValueB64));

        $this->assertSame(1, $verificado, 'La firma RSA-SHA1 sobre el SignedInfo canonicalizado debe verificar contra el certificado embebido.');

        
        
        $sinFirma = $doc->cloneNode(true);
        foreach (iterator_to_array($sinFirma->documentElement->childNodes) as $hijo) {
            if ($hijo instanceof \DOMElement && $hijo->localName === 'Signature') {
                $sinFirma->documentElement->removeChild($hijo);
            }
        }
        $digestEsperado = base64_encode(sha1($sinFirma->documentElement->C14N(false, false), true));
        $digestComprobanteReal = $references->item(2)->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'DigestValue')->item(0)->textContent;
        $this->assertSame($digestEsperado, $digestComprobanteReal);
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
