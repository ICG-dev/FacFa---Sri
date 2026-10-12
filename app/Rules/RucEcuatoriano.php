<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RucEcuatoriano implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::esValido($value)) {
            $fail('El RUC debe tener 13 dígitos, provincia 01–24 o 30, tipo de contribuyente válido y establecimiento distinto de 000. Para persona natural, la cédula debe ser válida.');
        }
    }

    public static function esValido(string $ruc): bool
    {
        if (! preg_match('/^[0-9]{13}$/D', $ruc) || substr($ruc, -3) === '000') {
            return false;
        }

        $provincia = (int) substr($ruc, 0, 2);
        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30)) {
            return false;
        }

        $tercero = (int) $ruc[2];
        if ($tercero < 6) {
            return CedulaEcuatoriana::esValida(substr($ruc, 0, 10));
        }

        // Sociedades: los RUC nuevos del SRI no siempre cumplen el antiguo módulo 11.
        return in_array($tercero, [6, 9], true);
    }
}
