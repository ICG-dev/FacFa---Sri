<?php

namespace Tests\Unit\ReglasFacturacion;

use App\Models\Empresa;
use App\Models\ReglaFacturacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReglaFacturacionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_primer_ciclo_y_periodos_de_calendario(): void
    {
        $regla = new ReglaFacturacion(['frecuencia' => 'mensual', 'dia_corte' => 5, 'fecha_inicio' => '2026-10-19']);
        $this->assertSame('2026-11-05', $regla->primerCiclo()->toDateString());
        $regla->frecuencia = 'semanal';
        $regla->dia_corte = 3;
        $this->assertSame('2026-10-21', $regla->primerCiclo()->toDateString());
        $this->assertSame('2020-W53', $regla->periodo(Carbon::parse('2021-01-01')));
        $regla->frecuencia = 'anual';
        $regla->proxima_ejecucion_at = '2024-02-29';
        $this->assertSame('2025-02-28', $regla->calcularProximoCiclo()->toDateString());
    }

    public function test_calendario_y_relaciones(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $regla = ReglaFacturacion::create([
            'empresa_id' => $empresa->id, 'user_id' => $usuario->id,
            'nombre' => 'Mensual', 'tipo_emision' => 'revision', 'frecuencia' => 'mensual',
            'fecha_inicio' => '2026-01-28', 'dia_corte' => 28, 'proxima_ejecucion_at' => '2026-01-28 06:00:00',
        ]);
        $this->assertSame('2026-02-28', $regla->calcularProximoCiclo()->toDateString());
        $this->assertTrue($regla->fresh()->activo);
        $this->assertTrue($empresa->reglasFacturacion->first()->is($regla));
        $cliente = $regla->clientes()->create(['tipo_identificacion' => '06', 'identificacion' => 'ABC123', 'razon_social' => 'Cliente']);
        $this->assertTrue($cliente->regla->is($regla));
        $datos = ['cliente_identificacion' => 'ABC123', 'periodo' => '2026-01', 'estado' => 'exitoso', 'modo_emision' => 'revision', 'ejecutado_at' => now()];
        $regla->ejecuciones()->create($datos);
        $this->expectException(QueryException::class);
        $regla->ejecuciones()->create($datos);
    }
}
