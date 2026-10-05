<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;




class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'codigo_principal' => fake()->unique()->bothify('PRD-####'),
            'descripcion' => fake()->words(3, true),
            'precio_unitario' => fake()->randomFloat(2, 1, 500),
            'codigo_impuesto' => 2,
            'tarifa' => 15,
        ];
    }
}
