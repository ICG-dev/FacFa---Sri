<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifaIvaSeeder extends Seeder
{
    public function run(): void
    {
        // Ficha técnica SRI v2.34, tabla 17. El código 8 aplica a turismo en fechas habilitadas.
        DB::table('tarifas_iva')->upsert([
            ['codigo_porcentaje' => '0', 'porcentaje' => '0.00', 'descripcion' => 'IVA 0%', 'activo' => true],
            ['codigo_porcentaje' => '2', 'porcentaje' => '12.00', 'descripcion' => 'IVA 12%', 'activo' => false],
            ['codigo_porcentaje' => '3', 'porcentaje' => '14.00', 'descripcion' => 'IVA 14%', 'activo' => false],
            ['codigo_porcentaje' => '4', 'porcentaje' => '15.00', 'descripcion' => 'IVA 15%', 'activo' => true],
            ['codigo_porcentaje' => '5', 'porcentaje' => '5.00', 'descripcion' => 'IVA 5%', 'activo' => true],
            ['codigo_porcentaje' => '6', 'porcentaje' => '0.00', 'descripcion' => 'No objeto de IVA', 'activo' => true],
            ['codigo_porcentaje' => '7', 'porcentaje' => '0.00', 'descripcion' => 'Exento de IVA', 'activo' => true],
            ['codigo_porcentaje' => '8', 'porcentaje' => '8.00', 'descripcion' => 'IVA diferenciado 8%', 'activo' => true],
            ['codigo_porcentaje' => '10', 'porcentaje' => '13.00', 'descripcion' => 'IVA 13%', 'activo' => true],
        ], ['codigo_porcentaje'], ['porcentaje', 'descripcion', 'activo']);
    }
}
