<?php

namespace App\Services\Sri;

use App\Models\Factura;
use Carbon\CarbonImmutable;









class AnulacionElegibilidadService
{
    public const VIA_EN_LINEA = 'en_linea';

    public const VIA_NOTA_CREDITO = 'nota_credito';

    public const VIA_NO_DISPONIBLE = 'no_disponible';

    public function esConsumidorFinal(Factura $factura): bool
    {
        return $factura->identificacion_comprador === config('sri.identificacion_consumidor_final');
    }

    public function fechaLimiteEnLinea(Factura $factura): CarbonImmutable
    {
        return CarbonImmutable::parse($factura->fecha_emision)
            ->addMonthNoOverflow()
            ->startOfMonth()
            ->addDays(6)
            ->endOfDay();
    }

    public function fechaLimiteNotaCredito(Factura $factura): CarbonImmutable
    {
        return CarbonImmutable::parse($factura->fecha_emision)->addMonthsNoOverflow(12)->endOfDay();
    }

    public function via(Factura $factura): string
    {
        if ($this->esConsumidorFinal($factura)) {
            return self::VIA_NO_DISPONIBLE;
        }

        $hoy = CarbonImmutable::now();

        if ($hoy->lessThanOrEqualTo($this->fechaLimiteEnLinea($factura))) {
            return self::VIA_EN_LINEA;
        }

        if ($hoy->lessThanOrEqualTo($this->fechaLimiteNotaCredito($factura))) {
            return self::VIA_NOTA_CREDITO;
        }

        return self::VIA_NO_DISPONIBLE;
    }
}
