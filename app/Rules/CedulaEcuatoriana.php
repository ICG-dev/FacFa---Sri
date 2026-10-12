<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CedulaEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::esValida($value)) {
            $fail('La cédula debe tener 10 dígitos, provincia 01–24 o 30, tercer dígito menor que 6 y dígito verificador válido.');
        }
    }

    public static function esValida(string $cedula): bool
    {
        if (! preg_match('/^[0-9]{10}$/D', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30) || (int) $cedula[2] >= 6) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = (int) $cedula[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $valor > 9 ? $valor - 9 : $valor;
        }

        return (10 - $suma % 10) % 10 === (int) $cedula[9];
    }
}
