<?php

namespace App\Services\Sri;

use App\Models\TarifaIva;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;

class FacturaCalculoService
{
    public function calcular(array $lineas, bool $previsualizar = false): array
    {
        $catalogo = TarifaIva::activas()->get()->keyBy('codigo_porcentaje');
        $detalles = [];

        foreach ($lineas as $indice => $linea) {
            try {
                $errores = $this->erroresLinea($linea);
                if ($errores) {
                    throw new DomainException(reset($errores));
                }
                $tarifa = $catalogo->get((string) ($linea['codigo_porcentaje'] ?? ''));
                if (! $tarifa) {
                    throw new DomainException('Seleccione una tarifa de IVA activa del catálogo.');
                }
                $detalles[$indice] = $this->detalle($linea, $tarifa);
            } catch (DomainException $e) {
                if (! $previsualizar) {
                    throw $e;
                }
                // Una entrada incompleta no participa en la previsualización y se valida antes de guardar.
            }
        }

        return ['detalles' => $detalles, 'totales' => $this->sumarDetalles($detalles)];
    }

    public function erroresLinea(array $linea): array
    {
        $errores = [];
        foreach (['cantidad', 'precio_unitario', 'subsidio', 'descuento'] as $campo) {
            $valor = (string) ($linea[$campo] ?? ($campo === 'cantidad' ? '' : '0'));
            $decimales = $campo === 'descuento' ? 2 : 6;
            if (! preg_match('/^\\d+(?:\\.\\d{1,'.$decimales.'})?$/D', $valor)) {
                $errores[$campo] = "Ingrese un valor decimal válido con hasta {$decimales} decimales.";

                continue;
            }
            if ($campo === 'cantidad' && BigDecimal::of($valor)->isZero()) {
                $errores[$campo] = 'La cantidad debe ser mayor que cero.';
            }
        }
        if ($errores) {
            return $errores;
        }

        $precio = BigDecimal::of((string) ($linea['precio_unitario'] ?? '0'));
        $subsidio = BigDecimal::of((string) ($linea['subsidio'] ?? '0'));
        if ($subsidio->isGreaterThan($precio)) {
            $errores['subsidio'] = 'El subsidio no puede superar el precio unitario.';
        }
        $subtotal = $precio->minus($subsidio)->multipliedBy((string) $linea['cantidad']);
        if (BigDecimal::of((string) ($linea['descuento'] ?? '0'))->isGreaterThan($subtotal)) {
            $errores['descuento'] = 'El descuento no puede superar el subtotal de la línea.';
        }

        return $errores;
    }

    private function detalle(array $linea, TarifaIva $tarifa): array
    {
        $cantidad = BigDecimal::of((string) $linea['cantidad'])->toScale(6);
        $precio = BigDecimal::of((string) ($linea['precio_unitario'] ?? '0'))->toScale(6);
        $subsidio = BigDecimal::of((string) ($linea['subsidio'] ?? '0'))->toScale(6);
        $descuento = BigDecimal::of((string) ($linea['descuento'] ?? '0'))->toScale(2, RoundingMode::HALF_UP);
        $neto = $precio->minus($subsidio);
        $base = $cantidad->multipliedBy($neto)->minus($descuento)->toScale(2, RoundingMode::HALF_UP);
        $impuesto = $base->multipliedBy($tarifa->porcentaje)->dividedBy('100', 2, RoundingMode::HALF_UP);

        return [
            'codigo_principal' => $linea['codigo_principal'] ?? '',
            'codigo_auxiliar' => $linea['codigo_auxiliar'] ?? null,
            'descripcion' => $linea['descripcion'] ?? '',
            'detalle_adicional' => $linea['detalle_adicional'] ?? null,
            'cantidad' => (string) $cantidad,
            'precio_unitario' => (string) $precio,
            'subsidio' => (string) $subsidio,
            'precio_sin_subsidio' => (string) $neto,
            'descuento' => (string) $descuento,
            'precio_total_sin_impuesto' => (string) $base,
            'codigo_impuesto' => 2,
            'codigo_porcentaje' => $tarifa->codigo_porcentaje,
            'tarifa' => $tarifa->porcentaje,
            'base_imponible' => (string) $base,
            'valor_impuesto' => (string) $impuesto,
        ];
    }

    public function sumarDetalles(iterable $detalles): array
    {
        $base = BigDecimal::zero();
        $descuento = BigDecimal::zero();
        $impuesto = BigDecimal::zero();
        $grupos = [];

        foreach ($detalles as $detalle) {
            $base = $base->plus($detalle['precio_total_sin_impuesto']);
            $descuento = $descuento->plus($detalle['descuento']);
            $impuesto = $impuesto->plus($detalle['valor_impuesto']);
            $clave = $detalle['codigo_impuesto'].'|'.$detalle['codigo_porcentaje'];
            $grupos[$clave] ??= [
                'codigo_impuesto' => (int) $detalle['codigo_impuesto'],
                'codigo_porcentaje' => (string) $detalle['codigo_porcentaje'],
                'base_imponible' => '0.00', 'valor' => '0.00',
            ];
            $grupos[$clave]['base_imponible'] = self::formatear(BigDecimal::of($grupos[$clave]['base_imponible'])->plus($detalle['base_imponible']));
            $grupos[$clave]['valor'] = self::formatear(BigDecimal::of($grupos[$clave]['valor'])->plus($detalle['valor_impuesto']));
        }

        // El total agrupado suma los impuestos redondeados de cada línea; no aplica la tarifa por segunda vez.
        return [
            'totalSinImpuestos' => self::formatear($base),
            'totalDescuento' => self::formatear($descuento),
            'totalImpuesto' => self::formatear($impuesto),
            'importeTotal' => self::formatear($base->plus($impuesto)),
            'totalConImpuestos' => array_values($grupos),
        ];
    }

    public static function formatear(mixed $valor, int $escala = 2): string
    {
        return (string) BigDecimal::of((string) $valor)->toScale($escala, RoundingMode::HALF_UP);
    }
}
