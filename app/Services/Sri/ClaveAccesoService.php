<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use App\Models\Factura;
use InvalidArgumentException;








class ClaveAccesoService
{
    private const COD_DOC_FACTURA = '01';

    private const TIPO_EMISION_NORMAL = '1';

    public function generar(Factura $factura, Empresa $empresa): string
    {
        if (is_null($factura->secuencial)) {
            throw new InvalidArgumentException('La factura no tiene un secuencial asignado.');
        }

        $fechaEmision = $factura->fecha_emision->format('dmY');
        $ruc = $empresa->ruc;
        $ambiente = (string) ($factura->ambiente ?? $empresa->ambiente);
        $serie = $factura->establecimiento.$factura->punto_emision;
        $secuencial = str_pad((string) $factura->secuencial, 9, '0', STR_PAD_LEFT);
        $codigoNumerico = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $clave48 = $fechaEmision
            .self::COD_DOC_FACTURA
            .$ruc
            .$ambiente
            .$serie
            .$secuencial
            .$codigoNumerico
            .self::TIPO_EMISION_NORMAL;

        return $clave48.$this->digitoVerificador($clave48);
    }

    



    public function digitoVerificador(string $cadena): int
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
            $suma += (int) $cadena[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $resto = $suma % 11;
        $digito = 11 - $resto;

        return match (true) {
            $digito === 11 => 0,
            $digito === 10 => 1,
            default => $digito,
        };
    }
}
