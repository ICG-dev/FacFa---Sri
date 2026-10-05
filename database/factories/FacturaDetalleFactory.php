<?php

namespace Database\Factories;

use App\Models\Factura;
use Illuminate\Database\Eloquent\Factories\Factory;




class FacturaDetalleFactory extends Factory
{
    public function definition(): array
    {
        $cantidad = 1;
        $precioUnitario = 100;
        $subtotal = $cantidad * $precioUnitario;
        $iva = round($subtotal * 0.15, 2);

        return [
            'factura_id' => Factura::factory(),
            'codigo_principal' => fake()->bothify('???-###'),
            'descripcion' => fake()->words(3, true),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subsidio' => 0,
            'precio_sin_subsidio' => $precioUnitario,
            'descuento' => 0,
            'precio_total_sin_impuesto' => $subtotal,
            'codigo_impuesto' => 2,
            'codigo_porcentaje' => '4',
            'tarifa' => 15,
            'base_imponible' => $subtotal,
            'valor_impuesto' => $iva,
        ];
    }
}
