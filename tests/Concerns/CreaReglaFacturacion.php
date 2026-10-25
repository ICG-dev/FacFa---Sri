<?php

namespace Tests\Concerns;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ReglaFacturacion;
use App\Models\User;

trait CreaReglaFacturacion
{
    protected function crearRegla(array $datos = []): ReglaFacturacion
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->for($empresa)->create();
        $regla = ReglaFacturacion::create(array_merge([
            'empresa_id' => $empresa->id, 'user_id' => $user->id, 'nombre' => 'Servicio mensual',
            'tipo_emision' => 'revision', 'frecuencia' => 'mensual', 'dia_corte' => 19,
            'fecha_inicio' => '2026-10-19', 'proxima_ejecucion_at' => '2026-10-19 06:00:00',
        ], $datos));
        $producto = Producto::factory()->create(['empresa_id' => $regla->empresa_id]);
        $regla->productos()->create(['producto_id' => $producto->id, 'cantidad' => '2', 'precio_unitario' => '10', 'descuento' => '1', 'codigo_porcentaje' => '4', 'tarifa' => '15']);
        $regla->clientes()->create(['tipo_identificacion' => '06', 'identificacion' => 'ABC123', 'razon_social' => 'Cliente de prueba', 'direccion' => 'Quito', 'correo' => 'cliente@example.com']);

        return $regla;
    }
}
