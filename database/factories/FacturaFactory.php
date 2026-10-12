<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacturaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'user_id' => User::factory(),
            'estado' => Factura::ESTADO_BORRADOR,
            'fecha_emision' => now()->toDateString(),
            'tipo_identificacion_comprador' => '05',
            'identificacion_comprador' => '0100000009',
            'razon_social_comprador' => fake()->name(),
            'direccion_comprador' => fake()->address(),
            'total_sin_impuestos' => 100,
            'total_descuento' => 0,
            'importe_total' => 112,
            'forma_pago' => 1,
        ];
    }

    public function consumidorFinal(): static
    {
        return $this->state(fn () => [
            'identificacion_comprador' => config('sri.identificacion_consumidor_final'),
            'tipo_identificacion_comprador' => '07',
            'razon_social_comprador' => 'CONSUMIDOR FINAL',
        ]);
    }

    public function autorizada(): static
    {
        return $this->state(fn () => [
            'estado' => Factura::ESTADO_AUTORIZADA,
            'establecimiento' => '001',
            'punto_emision' => '001',
            'secuencial' => fake()->unique()->numberBetween(1, 999999),
            'clave_acceso' => fake()->numerify(str_repeat('#', 49)),
            'numero_autorizacion' => fake()->numerify(str_repeat('#', 49)),
            'fecha_autorizacion' => now(),
            'enviado_at' => now()->subMinutes(5),
        ]);
    }
}
