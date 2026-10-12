<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, TarifaIvaSeeder::class]);

        $empresa = Empresa::create([
            'ruc' => '1792146739001',
            'razon_social' => 'EMPRESA DE PRUEBA S.A.',
            'nombre_comercial' => 'Empresa de Prueba',
            'dir_matriz' => 'Av. de Pruebas 123 y Calle Falsa',
            'obligado_contabilidad' => false,
            'establecimiento' => '001',
            'punto_emision' => '001',
            'ambiente' => 1,
        ]);

        $usuarios = [
            ['name' => 'Ana Facturadora', 'email' => 'facturador@example.com', 'rol' => 'facturador'],
            ['name' => 'Carlos Contador', 'email' => 'contador@example.com', 'rol' => 'contador'],
            ['name' => 'Admin Empresa', 'email' => 'admin@example.com', 'rol' => 'admin'],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::factory()->create([
                'empresa_id' => $empresa->id,
                'name' => $datos['name'],
                'email' => $datos['email'],
            ]);

            $usuario->assignRole($datos['rol']);
        }
    }
}
