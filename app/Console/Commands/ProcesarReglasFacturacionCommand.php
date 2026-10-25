<?php

namespace App\Console\Commands;

use App\Jobs\ProcesarReglaFacturacionJob;
use App\Models\ReglaFacturacion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcesarReglasFacturacionCommand extends Command
{
    protected $signature = 'reglas-facturacion:procesar {--force : Ejecutar el período actual aunque no haya vencido la próxima fecha}';

    protected $description = 'Despacha las reglas de facturación vigentes';

    public function handle(): int
    {
        $ahora = Carbon::now('America/Guayaquil');
        $force = (bool) $this->option('force');
        ReglaFacturacion::where('activo', true)->select(['id', 'empresa_id'])->chunkById(100, function ($reglas) use ($ahora, $force) {
            foreach ($reglas as $referencia) {
                DB::transaction(function () use ($referencia, $ahora, $force) {
                    $regla = ReglaFacturacion::where('empresa_id', $referencia->empresa_id)->lockForUpdate()->find($referencia->id);
                    if (! $regla || ! $regla->activo) {
                        return;
                    }
                    if ($regla->fecha_fin && $regla->fecha_fin->toDateString() < $ahora->toDateString()) {
                        $regla->update(['activo' => false]);

                        return;
                    }
                    if ($regla->fecha_inicio->toDateString() > $ahora->toDateString() || (! $force && $regla->proxima_ejecucion_at->gt($ahora))) {
                        return;
                    }
                    // Regla atrasada (anterior a hoy) factura el ciclo actual, no los perdidos.
                    $ciclo = ($force || $regla->proxima_ejecucion_at->lt($ahora->copy()->startOfDay())) ? $ahora : $regla->proxima_ejecucion_at;
                    // Publicar antes de avanzar permite reintentar si Redis falla.
                    // El bloqueo y la idempotencia protegen entregas duplicadas.
                    ProcesarReglaFacturacionJob::dispatch($regla->id, $regla->empresa_id, $ciclo->toDateString(), null, true)->beforeCommit();
                    if ($regla->frecuencia === 'unica') {
                        $regla->activo = false;
                    } else {
                        while ($regla->proxima_ejecucion_at->lte($ahora)) {
                            $regla->proxima_ejecucion_at = $regla->calcularProximoCiclo();
                        }
                    }
                    if ($regla->fecha_fin && $regla->proxima_ejecucion_at->toDateString() > $regla->fecha_fin->toDateString()) {
                        $regla->activo = false;
                    }
                    $regla->save();
                });
            }
        });
        $this->info('Reglas de facturación despachadas.');

        return self::SUCCESS;
    }
}
