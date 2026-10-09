<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use App\Models\Factura;
use DOMDocument;
use DOMElement;

class FacturaXmlBuilder
{
    public function build(Factura $factura, Empresa $empresa): string
    {
        $factura->loadMissing('detalles');

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;

        $root = $doc->createElement('factura');
        $root->setAttribute('id', 'comprobante');
        $root->setAttribute('version', '1.1.0');
        $doc->appendChild($root);

        $root->appendChild($this->infoTributaria($doc, $factura, $empresa));
        $root->appendChild($this->infoFactura($doc, $factura, $empresa));
        $root->appendChild($this->detalles($doc, $factura));
        $root->appendChild($this->infoAdicional($doc, $empresa));

        return $doc->saveXML();
    }

    private function infoTributaria(DOMDocument $doc, Factura $factura, Empresa $empresa): DOMElement
    {
        $info = $doc->createElement('infoTributaria');

        $this->append($doc, $info, 'ambiente', (string) ($factura->ambiente ?? $empresa->ambiente));
        $this->append($doc, $info, 'tipoEmision', '1');
        $this->append($doc, $info, 'razonSocial', $empresa->razon_social);

        if ($empresa->nombre_comercial) {
            $this->append($doc, $info, 'nombreComercial', $empresa->nombre_comercial);
        }

        $this->append($doc, $info, 'ruc', $empresa->ruc);
        $this->append($doc, $info, 'claveAcceso', $factura->clave_acceso);
        $this->append($doc, $info, 'codDoc', '01');
        $this->append($doc, $info, 'estab', $factura->establecimiento);
        $this->append($doc, $info, 'ptoEmi', $factura->punto_emision);
        $this->append($doc, $info, 'secuencial', str_pad((string) $factura->secuencial, 9, '0', STR_PAD_LEFT));
        $this->append($doc, $info, 'dirMatriz', $empresa->dir_matriz);

        return $info;
    }

    private function infoFactura(DOMDocument $doc, Factura $factura, Empresa $empresa): DOMElement
    {
        $info = $doc->createElement('infoFactura');

        $this->append($doc, $info, 'fechaEmision', $factura->fecha_emision->format('d/m/Y'));

        if ($empresa->contribuyente_especial) {
            $this->append($doc, $info, 'contribuyenteEspecial', $empresa->contribuyente_especial);
        }

        $this->append($doc, $info, 'obligadoContabilidad', $empresa->obligado_contabilidad ? 'SI' : 'NO');
        $this->append($doc, $info, 'tipoIdentificacionComprador', $factura->tipo_identificacion_comprador);
        $this->append($doc, $info, 'razonSocialComprador', $factura->razon_social_comprador);
        $this->append($doc, $info, 'identificacionComprador', $factura->identificacion_comprador);

        if ($factura->direccion_comprador) {
            $this->append($doc, $info, 'direccionComprador', $factura->direccion_comprador);
        }

        $this->append($doc, $info, 'totalSinImpuestos', $this->money($factura->total_sin_impuestos));
        $this->append($doc, $info, 'totalDescuento', $this->money($factura->total_descuento));

        $info->appendChild($this->totalConImpuestos($doc, $factura));

        $this->append($doc, $info, 'propina', '0.00');
        $this->append($doc, $info, 'importeTotal', $this->money($factura->importe_total));
        $this->append($doc, $info, 'moneda', 'DOLAR');

        $info->appendChild($this->pagos($doc, $factura));

        return $info;
    }

    private function totalConImpuestos(DOMDocument $doc, Factura $factura): DOMElement
    {
        $total = $doc->createElement('totalConImpuestos');

        foreach ($this->agruparImpuestos($factura) as $grupo) {
            $totalImpuesto = $doc->createElement('totalImpuesto');
            $this->append($doc, $totalImpuesto, 'codigo', (string) $grupo['codigo_impuesto']);
            $this->append($doc, $totalImpuesto, 'codigoPorcentaje', (string) $grupo['codigo_porcentaje']);
            $this->append($doc, $totalImpuesto, 'baseImponible', $this->money($grupo['base_imponible']));
            $this->append($doc, $totalImpuesto, 'valor', $this->money($grupo['valor']));
            $total->appendChild($totalImpuesto);
        }

        return $total;
    }

    private function agruparImpuestos(Factura $factura): array
    {
        return app(FacturaCalculoService::class)->sumarDetalles($factura->detalles)['totalConImpuestos'];
    }

    private function pagos(DOMDocument $doc, Factura $factura): DOMElement
    {
        $pagos = $doc->createElement('pagos');
        $pago = $doc->createElement('pago');

        $this->append($doc, $pago, 'formaPago', str_pad((string) ($factura->forma_pago ?? 1), 2, '0', STR_PAD_LEFT));
        $this->append($doc, $pago, 'total', $this->money($factura->importe_total));

        $pagos->appendChild($pago);

        return $pagos;
    }

    private function detalles(DOMDocument $doc, Factura $factura): DOMElement
    {
        $detalles = $doc->createElement('detalles');

        foreach ($factura->detalles as $linea) {
            $detalle = $doc->createElement('detalle');

            $this->append($doc, $detalle, 'codigoPrincipal', $linea->codigo_principal);
            $this->append($doc, $detalle, 'descripcion', $linea->descripcion);
            $this->append($doc, $detalle, 'cantidad', FacturaCalculoService::formatear($linea->cantidad, 6));
            $this->append($doc, $detalle, 'precioUnitario', FacturaCalculoService::formatear($linea->precio_sin_subsidio, 6));
            $this->append($doc, $detalle, 'descuento', $this->money($linea->descuento));
            $this->append($doc, $detalle, 'precioTotalSinImpuesto', $this->money($linea->precio_total_sin_impuesto));

            $impuestos = $doc->createElement('impuestos');
            $impuesto = $doc->createElement('impuesto');
            $this->append($doc, $impuesto, 'codigo', (string) $linea->codigo_impuesto);
            $this->append($doc, $impuesto, 'codigoPorcentaje', (string) $linea->codigo_porcentaje);
            $this->append($doc, $impuesto, 'tarifa', $this->money($linea->tarifa));
            $this->append($doc, $impuesto, 'baseImponible', $this->money($linea->base_imponible));
            $this->append($doc, $impuesto, 'valor', $this->money($linea->valor_impuesto));
            $impuestos->appendChild($impuesto);

            $detalle->appendChild($impuestos);
            $detalles->appendChild($detalle);
        }

        return $detalles;
    }

    private function infoAdicional(DOMDocument $doc, Empresa $empresa): DOMElement
    {
        $info = $doc->createElement('infoAdicional');

        $this->append($doc, $info, 'campoAdicional', $empresa->nombre_comercial ?? $empresa->razon_social)
            ->setAttribute('nombre', 'Nombre comercial');

        return $info;
    }

    private function append(DOMDocument $doc, DOMElement $parent, string $tag, string $value): DOMElement
    {

        $element = $doc->createElement($tag);
        $element->appendChild($doc->createTextNode($value));
        $parent->appendChild($element);

        return $element;
    }

    private function money(mixed $value): string
    {
        return FacturaCalculoService::formatear($value);
    }
}
