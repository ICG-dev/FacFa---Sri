<?php

namespace App\Actions\Facturas;

use App\Models\Factura;
use Illuminate\Support\Facades\DB;






class GuardarBorradorFacturaAction
{
    private const CODIGO_IMPUESTO_IVA = 2;

    



    public function ejecutar(?Factura $factura, int $empresaId, int $usuarioId, array $datosFactura, array $lineas): Factura
    {
        return DB::transaction(function () use ($factura, $empresaId, $usuarioId, $datosFactura, $lineas) {
            $totales = $this->calcularTotales($lineas);

            $facturaModel = $factura ?? new Factura([
                'empresa_id' => $empresaId,
                'user_id' => $usuarioId,
                'estado' => Factura::ESTADO_BORRADOR,
            ]);

            $facturaModel->fill([
                ...$datosFactura,
                'total_sin_impuestos' => $totales['totalSinImpuestos'],
                'total_descuento' => $totales['totalDescuento'],
                'importe_total' => $totales['importeTotal'],
            ]);
            $facturaModel->save();

            $facturaModel->detalles()->delete();

            foreach ($lineas as $linea) {
                $facturaModel->detalles()->create($this->calcularDetalle($linea));
            }

            return $facturaModel;
        });
    }

    



    public function calcularTotales(array $lineas): array
    {
        $totalSinImpuestos = 0;
        $totalDescuento = 0;
        $totalImpuesto = 0;

        foreach ($lineas as $linea) {
            $detalle = $this->calcularDetalle($linea);
            $totalSinImpuestos += $detalle['precio_total_sin_impuesto'];
            $totalDescuento += $detalle['descuento'];
            $totalImpuesto += $detalle['valor_impuesto'];
        }

        return [
            'totalSinImpuestos' => round($totalSinImpuestos, 2),
            'totalDescuento' => round($totalDescuento, 2),
            'totalImpuesto' => round($totalImpuesto, 2),
            'importeTotal' => round($totalSinImpuestos + $totalImpuesto, 2),
        ];
    }

    


    private function calcularDetalle(array $linea): array
    {
        $cantidad = (float) ($linea['cantidad'] ?? 0);
        $precioUnitario = (float) ($linea['precio_unitario'] ?? 0);
        $subsidio = (float) ($linea['subsidio'] ?? 0);
        $descuento = round((float) ($linea['descuento'] ?? 0), 2);
        $tarifa = (float) ($linea['tarifa'] ?? 0);
        $precioSinSubsidio = $precioUnitario - $subsidio;
        $subtotal = round(($cantidad * $precioSinSubsidio) - $descuento, 2);

        return [
            'codigo_principal' => $linea['codigo_principal'] ?? '',
            'codigo_auxiliar' => $linea['codigo_auxiliar'] ?? null,
            'descripcion' => $linea['descripcion'] ?? '',
            'detalle_adicional' => $linea['detalle_adicional'] ?? null,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subsidio' => $subsidio,
            'precio_sin_subsidio' => $precioSinSubsidio,
            'descuento' => $descuento,
            'precio_total_sin_impuesto' => $subtotal,
            'codigo_impuesto' => self::CODIGO_IMPUESTO_IVA,
            'codigo_porcentaje' => match ($tarifa) {
                0.0 => '0',
                5.0 => '5',
                8.0 => '8',
                15.0 => '4',
                default => throw new \DomainException('Tarifa de IVA no admitida.'),
            },
            'tarifa' => $tarifa,
            'base_imponible' => $subtotal,
            'valor_impuesto' => round($subtotal * ($tarifa / 100), 2),
        ];
    }
}
