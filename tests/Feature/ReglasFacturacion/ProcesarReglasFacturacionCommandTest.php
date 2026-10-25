<?php

namespace Tests\Feature\ReglasFacturacion;

use App\Jobs\ProcesarReglaFacturacionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\CreaReglaFacturacion;
use Tests\TestCase;

class ProcesarReglasFacturacionCommandTest extends TestCase
{
    use CreaReglaFacturacion, RefreshDatabase;

    public function test_despacha_elegibles_avanza_ciclo_y_desactiva_finalizadas(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(8, 0));
        Bus::fake();
        $regla = $this->crearRegla();
        $unica = $this->crearRegla(['frecuencia' => 'unica']);
        $fin = $this->crearRegla(['fecha_fin' => '2026-10-19']);
        $vencida = $this->crearRegla(['fecha_fin' => '2026-10-18']);
        $this->crearRegla(['activo' => false]);
        $this->crearRegla(['fecha_inicio' => '2026-11-01']);
        $this->crearRegla(['proxima_ejecucion_at' => '2026-11-19']);
        $this->artisan('reglas-facturacion:procesar')->assertSuccessful();
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, 3);
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, fn ($job) => $job->reglaId === $regla->id && $job->ciclo === '2026-10-19' && $job->empresaId === $regla->empresa_id);
        $this->assertSame('2026-11-19', $regla->fresh()->proxima_ejecucion_at->toDateString());
        foreach ([$unica, $fin, $vencida] as $finalizada) {
            $this->assertFalse($finalizada->fresh()->activo);
        }
        $this->artisan('reglas-facturacion:procesar')->assertSuccessful();
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, 3);
    }

    public function test_regla_atrasada_usa_ahora_como_ciclo_y_avanza_al_futuro(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(8, 0));
        Bus::fake();
        $regla = $this->crearRegla(['proxima_ejecucion_at' => '2026-07-19 06:00:00']);
        $this->artisan('reglas-facturacion:procesar')->assertSuccessful();
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, 1);
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, fn ($job) => $job->ciclo === '2026-10-19');
        $this->assertSame('2026-11-19', $regla->fresh()->proxima_ejecucion_at->toDateString());
    }

    public function test_force_usa_periodo_actual_sin_adelantar_ciclo_futuro(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(8, 0));
        Bus::fake();
        $regla = $this->crearRegla(['proxima_ejecucion_at' => '2026-11-19']);
        $this->artisan('reglas-facturacion:procesar --force')->assertSuccessful();
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, fn ($job) => $job->ciclo === '2026-10-19');
        $this->assertSame('2026-11-19', $regla->fresh()->proxima_ejecucion_at->toDateString());
    }
}
