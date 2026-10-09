<?php

namespace App\Actions\Facturas;

use App\Models\Factura;
use App\Services\Sri\FacturaCalculoService;
use Illuminate\Support\Facades\DB;

class GuardarBorradorFacturaAction
{
    public function ejecutar(?Factura $factura, int $empresaId, int $usuarioId, array $datosFactura, array $lineas): Factura
    {
        return DB::transaction(function () use ($factura, $empresaId, $usuarioId, $datosFactura, $lineas) {
            $calculo = app(FacturaCalculoService::class)->calcular($lineas);
            $totales = $calculo['totales'];
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
            $facturaModel->detalles()->createMany($calculo['detalles']);

            return $facturaModel;
        });
    }

    public function calcularTotales(array $lineas): array
    {
        return app(FacturaCalculoService::class)->calcular($lineas)['totales'];
    }
}
