<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReglaFacturacion extends Model
{
    protected $table = 'reglas_facturacion';

    protected $attributes = ['activo' => true, 'forma_pago' => '01', 'unidad_tiempo' => 'dias'];

    protected $fillable = ['empresa_id', 'user_id', 'nombre', 'descripcion', 'tipo_emision', 'frecuencia', 'dia_corte', 'fecha_inicio', 'fecha_fin', 'proxima_ejecucion_at', 'forma_pago', 'tiempo_plazo', 'unidad_tiempo', 'activo'];

    protected $casts = ['activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date', 'proxima_ejecucion_at' => 'datetime', 'dia_corte' => 'integer'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(ReglaFacturacionProducto::class, 'regla_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(ReglaFacturacionCliente::class, 'regla_id');
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(ReglaFacturacionEjecucion::class, 'regla_id');
    }

    public function primerCiclo(): Carbon
    {
        $fecha = $this->fecha_inicio->copy()->setTime(6, 0);
        if ($this->frecuencia === 'mensual' && $this->dia_corte) {
            $fecha->day($this->dia_corte);
            if ($fecha->lt($this->fecha_inicio)) {
                $fecha->addMonthNoOverflow();
            }
        } elseif ($this->frecuencia === 'semanal' && $this->dia_corte) {
            $fecha->addDays(($this->dia_corte - $fecha->dayOfWeekIso + 7) % 7);
        }

        return $fecha;
    }

    public function calcularProximoCiclo(): Carbon
    {
        $fecha = $this->proxima_ejecucion_at->copy();

        return match ($this->frecuencia) {
            'mensual' => $fecha->addMonthNoOverflow()->day($this->dia_corte ?? min($this->fecha_inicio->day, $fecha->daysInMonth)),
            'anual' => $fecha->addYearNoOverflow(),
            'semanal' => $fecha->addWeek(),
            default => $fecha->addDay(),
        };
    }

    public function periodo(Carbon $fecha): string
    {
        return match ($this->frecuencia) {
            'mensual' => $fecha->format('Y-m'),
            'anual' => $fecha->format('Y'),
            'semanal' => $fecha->format('o-\WW'),
            'unica' => 'unica',
            default => $fecha->toDateString(),
        };
    }
}
