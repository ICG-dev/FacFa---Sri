<?php

namespace App\Services\Sri;

use App\Models\Factura;
use App\Models\FormaPago;
use Barryvdh\DomPDF\Facade\Pdf;
use Brick\Math\BigDecimal;
use DomainException;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Response;
use Picqer\Barcode\BarcodeGeneratorSVG;

class RideGenerator
{
    public function generar(Factura $factura): Response
    {
        $datos = $this->datos($factura);

        return Pdf::loadView('pdf.ride', $datos)
            ->setPaper('a4')
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'isFontSubsettingEnabled' => true,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ])
            ->download('RIDE-'.$datos['numero_factura'].'.pdf')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function datos(Factura $factura): array
    {
        if (! in_array($factura->estado, [Factura::ESTADO_AUTORIZADA, Factura::ESTADO_ANULADA], true)) {
            throw new DomainException('El RIDE solo está disponible para facturas autorizadas o anuladas.');
        }

        if (! preg_match('/\A[0-9]{49}\z/', $factura->clave_acceso ?? '')
            || ! $factura->numero_autorizacion
            || ! $factura->fecha_autorizacion
            || ! $factura->establecimiento
            || ! $factura->punto_emision
            || ! $factura->secuencial) {
            throw new DomainException('La factura no tiene datos completos de autorización para generar el RIDE.');
        }

        $factura->loadMissing('empresa', 'detalles');
        $xml = $this->xmlGuardado($factura);
        $impuestos = [];
        $iva = BigDecimal::of('0.00');

        // Sumar únicamente las bases y los impuestos almacenados; no recalcular tarifas.
        foreach ($factura->detalles as $detalle) {
            $clave = $detalle->codigo_impuesto.'|'.$detalle->codigo_porcentaje;
            $impuestos[$clave] ??= [
                'codigo' => (string) $detalle->codigo_impuesto,
                'etiqueta' => $this->etiquetaImpuesto($detalle->codigo_impuesto, $detalle->codigo_porcentaje, $detalle->tarifa),
                'base' => '0.00',
                'valor' => '0.00',
            ];
            $impuestos[$clave]['base'] = (string) BigDecimal::of($impuestos[$clave]['base'])->plus($detalle->base_imponible)->toScale(2);
            $impuestos[$clave]['valor'] = (string) BigDecimal::of($impuestos[$clave]['valor'])->plus($detalle->valor_impuesto)->toScale(2);

            if ((int) $detalle->codigo_impuesto === 2) {
                $iva = $iva->plus($detalle->valor_impuesto);
            }
        }

        $formaPago = FormaPago::where('codigo', $factura->forma_pago)->first();
        $codigoPago = str_pad((string) $factura->forma_pago, 2, '0', STR_PAD_LEFT);
        $tipoEmision = $this->valorXml($xml, '/factura/infoTributaria/tipoEmision')
            ?? substr($factura->clave_acceso, 47, 1);
        $barcode = new BarcodeGeneratorSVG;

        return [
            'factura' => $factura,
            'emisor' => $this->emisor($factura, $xml),
            'numero_factura' => str_pad($factura->establecimiento, 3, '0', STR_PAD_LEFT)
                .'-'.str_pad($factura->punto_emision, 3, '0', STR_PAD_LEFT)
                .'-'.str_pad((string) $factura->secuencial, 9, '0', STR_PAD_LEFT),
            'ambiente' => match ($factura->ambienteEmision()) {
                1 => 'PRUEBAS',
                2 => 'PRODUCCIÓN',
                default => throw new DomainException('El ambiente de emisión de la factura no es válido.'),
            },
            'tipo_emision' => match ($tipoEmision) {
                '1' => 'NORMAL',
                '2' => 'CONTINGENCIA',
                default => throw new DomainException('El tipo de emisión de la factura no es válido.'),
            },
            'impuestos' => $impuestos,
            'iva_total' => (string) $iva->toScale(2),
            'pagos' => [[
                'codigo' => $codigoPago,
                'descripcion' => $formaPago?->descripcion ?? 'Forma de pago '.$codigoPago,
                'total' => $factura->importe_total,
            ]],
            'barcode' => 'data:image/svg+xml;base64,'.base64_encode(
                $barcode->getBarcode($factura->clave_acceso, $barcode::TYPE_CODE_128, 1, 50)
            ),
        ];
    }

    private function emisor(Factura $factura, ?DOMXPath $xml): array
    {
        $empresa = $factura->empresa;
        $matriz = $this->valorXml($xml, '/factura/infoTributaria/dirMatriz') ?? $empresa->dir_matriz;

        return [
            'ruc' => $this->valorXml($xml, '/factura/infoTributaria/ruc') ?? $empresa->ruc,
            'razon_social' => $this->valorXml($xml, '/factura/infoTributaria/razonSocial') ?? $empresa->razon_social,
            'nombre_comercial' => $this->valorXml($xml, '/factura/infoTributaria/nombreComercial') ?? $empresa->nombre_comercial,
            'dir_matriz' => $matriz,
            'dir_establecimiento' => $this->valorXml($xml, '/factura/infoFactura/dirEstablecimiento') ?? $matriz,
            'obligado_contabilidad' => $this->valorXml($xml, '/factura/infoFactura/obligadoContabilidad')
                ?? ($empresa->obligado_contabilidad ? 'SI' : 'NO'),
            'contribuyente_especial' => $this->valorXml($xml, '/factura/infoFactura/contribuyenteEspecial') ?? $empresa->contribuyente_especial,
        ];
    }

    private function xmlGuardado(Factura $factura): ?DOMXPath
    {
        $contenido = $factura->xml_autorizado ?: $factura->xml_generado ?: $factura->xml_firmado;

        if (! $contenido) {
            return null;
        }

        // No resolver entidades ni permitir accesos a archivos o redes desde el XML.
        if (stripos($contenido, '<!DOCTYPE') !== false || stripos($contenido, '<!ENTITY') !== false) {
            throw new DomainException('El XML guardado contiene declaraciones no permitidas.');
        }

        $anterior = libxml_use_internal_errors(true);

        try {
            $doc = new DOMDocument;
            if (! $doc->loadXML($contenido, LIBXML_NONET) || $doc->documentElement?->tagName !== 'factura') {
                throw new DomainException('El XML guardado de la factura no es válido.');
            }

            return new DOMXPath($doc);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }

    private function valorXml(?DOMXPath $xml, string $ruta): ?string
    {
        if ($xml === null) {
            return null;
        }

        $valor = trim($xml->evaluate('string('.$ruta.')'));

        return $valor === '' ? null : $valor;
    }

    private function etiquetaImpuesto(int $codigo, string $porcentaje, string $tarifa): string
    {
        if ($codigo !== 2) {
            return match ($codigo) {
                3 => 'ICE',
                5 => 'IRBPNR',
                default => 'IMPUESTO '.$codigo,
            };
        }

        return match ($porcentaje) {
            '6' => 'NO OBJETO DE IVA',
            '7' => 'EXENTO DE IVA',
            default => 'IVA '.$tarifa.'%',
        };
    }
}
