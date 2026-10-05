<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;




class EmpresaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ruc' => fake()->numerify('##########001'),
            'razon_social' => fake()->company(),
            'nombre_comercial' => fake()->company(),
            'dir_matriz' => fake()->address(),
            'obligado_contabilidad' => fake()->boolean(),
            'establecimiento' => '001',
            'punto_emision' => '001',
            'ambiente' => 1,
        ];
    }
}
