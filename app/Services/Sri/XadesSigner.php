<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Storage;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;





















class XadesSigner
{
    private const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';

    private const NS_ETSI = 'http://uri.etsi.org/01903/v1.3.2#';

    private const C14N_ALGORITHM = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    private const SHA1_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#sha1';

    private const RSA_SHA1_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    private const ENVELOPED_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    public function firmar(string $xml, Empresa $empresa): string
    {
        [$certificadoPem, $llavePrivadaPem] = $this->leerCertificado($empresa);
        $certificadoDer = $this->pemADer($certificadoPem);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        $doc->loadXML($xml);

        $sufijo = (string) random_int(100000, 999999);
        $signatureId = "Signature{$sufijo}";
        $signedPropertiesId = "{$signatureId}-SignedProperties{$sufijo}";
        $certificateId = "Certificate{$sufijo}";
        $referenceComprobanteId = "Reference-ID-{$sufijo}";
        $objectId = "{$signatureId}-Object{$sufijo}";

        $refSignedProperties = $this->construirReference(
            $doc,
            uri: "#{$signedPropertiesId}",
            id: 'SignedPropertiesID'.random_int(100000, 999999),
            type: 'http://uri.etsi.org/01903#SignedProperties',
        );
        $refCertificado = $this->construirReference($doc, uri: "#{$certificateId}");
        $refComprobante = $this->construirReference(
            $doc,
            uri: '#comprobante',
            id: $referenceComprobanteId,
            enveloped: true,
        );

        $signedInfo = $this->construirSignedInfo($doc, $signatureId, [
            $refSignedProperties['reference'],
            $refCertificado['reference'],
            $refComprobante['reference'],
        ]);

        $signedProperties = $this->construirSignedProperties(
            $doc,
            $signedPropertiesId,
            $referenceComprobanteId,
            $certificadoDer,
            $certificadoPem,
        );

        $signatureValueEl = $doc->createElementNS(self::NS_DS, 'ds:SignatureValue');
        $signatureValueEl->setAttribute('Id', 'SignatureValue'.random_int(100000, 999999));

        $signature = $this->construirSignature(
            $doc,
            $signatureId,
            $certificateId,
            $objectId,
            $signedInfo,
            $signatureValueEl,
            $certificadoPem,
            $signedProperties,
        );

        
        
        $doc->documentElement->appendChild($signature);

        $refComprobante['digestValue']->nodeValue = $this->digestComprobanteEnveloped($doc, $signature);
        $refSignedProperties['digestValue']->nodeValue = $this->digestC14N($signedProperties);
        $refCertificado['digestValue']->nodeValue = $this->digestC14N(
            $signature->getElementsByTagNameNS(self::NS_DS, 'KeyInfo')->item(0)
        );

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $key->loadKey($llavePrivadaPem);
        $signatureValueEl->nodeValue = base64_encode($key->signData($this->canonicalizar($signedInfo)));

        return $doc->saveXML();
    }

    private function construirSignedInfo(DOMDocument $doc, string $signatureId, array $references): DOMElement
    {
        $signedInfo = $doc->createElementNS(self::NS_DS, 'ds:SignedInfo');
        $signedInfo->setAttribute('Id', "{$signatureId}-SignedInfo".random_int(100000, 999999));
        $this->declararNamespaces($signedInfo);

        $c14n = $doc->createElementNS(self::NS_DS, 'ds:CanonicalizationMethod');
        $c14n->setAttribute('Algorithm', self::C14N_ALGORITHM);
        $signedInfo->appendChild($c14n);

        $signatureMethod = $doc->createElementNS(self::NS_DS, 'ds:SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', self::RSA_SHA1_ALGORITHM);
        $signedInfo->appendChild($signatureMethod);

        foreach ($references as $reference) {
            $signedInfo->appendChild($reference);
        }

        return $signedInfo;
    }

    


    private function construirReference(
        DOMDocument $doc,
        string $uri,
        ?string $id = null,
        ?string $type = null,
        bool $enveloped = false,
    ): array {
        $reference = $doc->createElementNS(self::NS_DS, 'ds:Reference');
        if ($id) {
            $reference->setAttribute('Id', $id);
        }
        if ($type) {
            $reference->setAttribute('Type', $type);
        }
        $reference->setAttribute('URI', $uri);

        if ($enveloped) {
            $transforms = $doc->createElementNS(self::NS_DS, 'ds:Transforms');
            $transform = $doc->createElementNS(self::NS_DS, 'ds:Transform');
            $transform->setAttribute('Algorithm', self::ENVELOPED_ALGORITHM);
            $transforms->appendChild($transform);
            $reference->appendChild($transforms);
        }

        $digestMethod = $doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::SHA1_ALGORITHM);
        $reference->appendChild($digestMethod);

        
        $digestValue = $doc->createElementNS(self::NS_DS, 'ds:DigestValue');
        $reference->appendChild($digestValue);

        return ['reference' => $reference, 'digestValue' => $digestValue];
    }

    private function construirSignature(
        DOMDocument $doc,
        string $signatureId,
        string $certificateId,
        string $objectId,
        DOMElement $signedInfo,
        DOMElement $signatureValueEl,
        string $certificadoPem,
        DOMElement $signedProperties,
    ): DOMElement {
        $signature = $doc->createElementNS(self::NS_DS, 'ds:Signature');
        $signature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etsi', self::NS_ETSI);
        $signature->setAttribute('Id', $signatureId);
        $signature->appendChild($signedInfo);
        $signature->appendChild($signatureValueEl);

        $signature->appendChild($this->construirKeyInfo($doc, $certificateId, $certificadoPem));

        $object = $doc->createElementNS(self::NS_DS, 'ds:Object');
        $object->setAttribute('Id', $objectId);

        $qualifyingProperties = $doc->createElementNS(self::NS_ETSI, 'etsi:QualifyingProperties');
        $qualifyingProperties->setAttribute('Target', "#{$signatureId}");
        $qualifyingProperties->appendChild($signedProperties);

        $object->appendChild($qualifyingProperties);
        $signature->appendChild($object);

        return $signature;
    }

    private function construirKeyInfo(DOMDocument $doc, string $certificateId, string $certificadoPem): DOMElement
    {
        $keyInfo = $doc->createElementNS(self::NS_DS, 'ds:KeyInfo');
        $keyInfo->setAttribute('Id', $certificateId);
        $this->declararNamespaces($keyInfo);

        $x509Data = $doc->createElementNS(self::NS_DS, 'ds:X509Data');
        $x509Certificate = $doc->createElementNS(self::NS_DS, 'ds:X509Certificate');
        $x509Certificate->appendChild($doc->createTextNode($this->limpiarPem($certificadoPem)));
        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);

        $publicKey = openssl_pkey_get_public($certificadoPem);
        $detalles = openssl_pkey_get_details($publicKey);

        $keyValue = $doc->createElementNS(self::NS_DS, 'ds:KeyValue');
        $rsaKeyValue = $doc->createElementNS(self::NS_DS, 'ds:RSAKeyValue');

        $modulus = $doc->createElementNS(self::NS_DS, 'ds:Modulus');
        $modulus->appendChild($doc->createTextNode(base64_encode($detalles['rsa']['n'])));
        $rsaKeyValue->appendChild($modulus);

        $exponent = $doc->createElementNS(self::NS_DS, 'ds:Exponent');
        $exponent->appendChild($doc->createTextNode(base64_encode($detalles['rsa']['e'])));
        $rsaKeyValue->appendChild($exponent);

        $keyValue->appendChild($rsaKeyValue);
        $keyInfo->appendChild($keyValue);

        return $keyInfo;
    }

    private function construirSignedProperties(
        DOMDocument $doc,
        string $signedPropertiesId,
        string $referenceComprobanteId,
        string $certificadoDer,
        string $certificadoPem,
    ): DOMElement {
        $signedProperties = $doc->createElementNS(self::NS_ETSI, 'etsi:SignedProperties');
        $signedProperties->setAttribute('Id', $signedPropertiesId);
        $this->declararNamespaces($signedProperties);

        $signedSignatureProperties = $doc->createElementNS(self::NS_ETSI, 'etsi:SignedSignatureProperties');

        $signingTime = $doc->createElementNS(self::NS_ETSI, 'etsi:SigningTime');
        $signingTime->appendChild($doc->createTextNode((new DateTimeImmutable('now'))->format('Y-m-d\TH:i:sP')));
        $signedSignatureProperties->appendChild($signingTime);

        $signedSignatureProperties->appendChild(
            $this->construirSigningCertificate($doc, $certificadoDer, $certificadoPem)
        );

        $signedProperties->appendChild($signedSignatureProperties);

        $signedDataObjectProperties = $doc->createElementNS(self::NS_ETSI, 'etsi:SignedDataObjectProperties');
        $dataObjectFormat = $doc->createElementNS(self::NS_ETSI, 'etsi:DataObjectFormat');
        $dataObjectFormat->setAttribute('ObjectReference', "#{$referenceComprobanteId}");

        $description = $doc->createElementNS(self::NS_ETSI, 'etsi:Description');
        $description->appendChild($doc->createTextNode('contenido comprobante'));
        $dataObjectFormat->appendChild($description);

        $mimeType = $doc->createElementNS(self::NS_ETSI, 'etsi:MimeType');
        $mimeType->appendChild($doc->createTextNode('text/xml'));
        $dataObjectFormat->appendChild($mimeType);

        $signedDataObjectProperties->appendChild($dataObjectFormat);
        $signedProperties->appendChild($signedDataObjectProperties);

        return $signedProperties;
    }

    private function construirSigningCertificate(DOMDocument $doc, string $certificadoDer, string $certificadoPem): DOMElement
    {
        $signingCertificate = $doc->createElementNS(self::NS_ETSI, 'etsi:SigningCertificate');
        $cert = $doc->createElementNS(self::NS_ETSI, 'etsi:Cert');

        $certDigest = $doc->createElementNS(self::NS_ETSI, 'etsi:CertDigest');
        $digestMethod = $doc->createElementNS(self::NS_DS, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', self::SHA1_ALGORITHM);
        $certDigest->appendChild($digestMethod);

        $digestValue = $doc->createElementNS(self::NS_DS, 'ds:DigestValue');
        $digestValue->appendChild($doc->createTextNode(base64_encode(sha1($certificadoDer, true))));
        $certDigest->appendChild($digestValue);
        $cert->appendChild($certDigest);

        $datosCertificado = openssl_x509_parse($certificadoPem);
        if ($datosCertificado === false) {
            throw new RuntimeException('No se pudo leer el certificado X509 del emisor.');
        }

        $issuerSerial = $doc->createElementNS(self::NS_ETSI, 'etsi:IssuerSerial');
        $issuerName = $doc->createElementNS(self::NS_DS, 'ds:X509IssuerName');
        $issuerName->appendChild($doc->createTextNode($this->formatearIssuer($datosCertificado['issuer'])));
        $issuerSerial->appendChild($issuerName);

        $serialNumber = $doc->createElementNS(self::NS_DS, 'ds:X509SerialNumber');
        $serialNumber->appendChild($doc->createTextNode((string) $datosCertificado['serialNumber']));
        $issuerSerial->appendChild($serialNumber);

        $cert->appendChild($issuerSerial);
        $signingCertificate->appendChild($cert);

        return $signingCertificate;
    }

    private function formatearIssuer(array $issuer): string
    {
        $orden = ['C', 'O', 'OU', 'L', 'ST', 'CN'];
        $partes = [];
        foreach (array_reverse($orden) as $campo) {
            if (isset($issuer[$campo])) {
                $partes[] = "{$campo}={$issuer[$campo]}";
            }
        }

        return implode(',', $partes);
    }

    




    private function digestComprobanteEnveloped(DOMDocument $doc, DOMElement $signature): string
    {
        $copia = $doc->cloneNode(true);
        $signatureId = $signature->getAttribute('Id');

        foreach (iterator_to_array($copia->documentElement->childNodes) as $hijo) {
            if ($hijo instanceof DOMElement && $hijo->getAttribute('Id') === $signatureId) {
                $copia->documentElement->removeChild($hijo);
                break;
            }
        }

        return base64_encode(sha1($copia->documentElement->C14N(false, false), true));
    }

    /** Declara ds y etsi en el propio elemento para que su C14N sea autocontenido. */
    private function declararNamespaces(DOMElement $element): void
    {
        $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', self::NS_DS);
        $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etsi', self::NS_ETSI);
    }

    private function digestC14N(DOMElement $element): string
    {
        return base64_encode(sha1($this->canonicalizar($element), true));
    }

    private function canonicalizar(DOMElement $element): string
    {
        return $element->C14N(false, false);
    }

    private function limpiarPem(string $pem): string
    {
        $pem = preg_replace('/-----(BEGIN|END) CERTIFICATE-----/', '', $pem);

        return trim(str_replace(["\r", "\n"], '', $pem));
    }

    private function pemADer(string $pem): string
    {
        return base64_decode($this->limpiarPem($pem));
    }

    


    private function leerCertificado(Empresa $empresa): array
    {
        if (! $empresa->certificado_path || ! Storage::disk('local')->exists($empresa->certificado_path)) {
            throw new RuntimeException("La empresa {$empresa->ruc} no tiene un certificado .p12 configurado.");
        }

        $contenidoP12 = Storage::disk('local')->get($empresa->certificado_path);

        $certificados = [];
        $exito = openssl_pkcs12_read($contenidoP12, $certificados, $empresa->certificado_password);

        if (! $exito) {
            throw new RuntimeException("No se pudo leer el certificado .p12 de la empresa {$empresa->ruc}: contraseña inválida o archivo corrupto.");
        }

        return [$certificados['cert'], $certificados['pkey']];
    }
}
