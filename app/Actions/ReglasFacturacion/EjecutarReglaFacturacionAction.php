<?php

namespace App\Actions\ReglasFacturacion;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use App\Actions\Sri\ConfirmarFacturaAction;
use App\Models\ReglaFacturacion;
use App\Models\User;
use App\Rules\CedulaEcuatoriana;
use App\Rules\RucEcuatoriano;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EjecutarReglaFacturacionAction
{
    public function ejecutar(ReglaFacturacion $regla, ?int $usuarioId = null, ?string $ciclo = null, bool $programada = false, ?string $clienteIdentificacion = null, ?string $periodoReintento = null): array
    {
        $fecha = $ciclo ? Carbon::parse($ciclo, 'America/Guayaquil') : Carbon::now('America/Guayaquil');
        $resumen = ['procesados' => 0, 'exitosos' => 0, 'fallidos' => 0, 'omitidos' => 0];
        $regla = ReglaFacturacion::where('empresa_id', $regla->empresa_id)->findOrFail($regla->id);
        if ((! $programada && ! $regla->activo) || $fecha->toDateString() < $regla->fecha_inicio->toDateString()
            || ($regla->fecha_fin && $fecha->toDateString() > $regla->fecha_fin->toDateString())) {
            return $resumen;
        }
        $periodo = $periodoReintento ?? $regla->periodo($fecha);
        $usuarioId ??= $regla->user_id;
        User::where('empresa_id', $regla->empresa_id)->findOrFail($usuarioId);

        $clientes = $regla->clientes()->where('activo', true)
            ->when($clienteIdentificacion !== null, fn ($q) => $q->where('identificacion', $clienteIdentificacion))->pluck('id');
        foreach ($clientes as $clienteId) {
            // El bloqueo del padre serializa también la primera inserción de auditoría.
            // El savepoint interior revierte la factura sin perder el bloqueo del padre.
            $estado = DB::transaction(function () use ($regla, $clienteId, $usuarioId, $periodo) {
                $actual = ReglaFacturacion::where('empresa_id', $regla->empresa_id)->lockForUpdate()->findOrFail($regla->id);
                $cliente = $actual->clientes()->where('activo', true)->find($clienteId);
                if (! $cliente) {
                    return 'omitidos';
                }
                $clave = ['cliente_identificacion' => $cliente->identificacion, 'periodo' => $periodo];
                if ($actual->ejecuciones()->where($clave)->where('estado', 'exitoso')->exists()) {
                    return 'omitidos';
                }
                try {
                    DB::transaction(function () use ($actual, $cliente, $usuarioId, $clave) {
                        Validator::make($cliente->toArray(), self::reglasCliente($cliente->tipo_identificacion))->validate();
                        $lineas = $actual->productos()->with('producto')->get()->map(function ($item) use ($actual) {
                            if (! $item->producto || $item->producto->empresa_id !== $actual->empresa_id) {
                                throw new \DomainException('El producto no pertenece a la empresa de la regla.');
                            }

                            return [...$item->toArray(), 'codigo_principal' => $item->producto->codigo_principal, 'descripcion' => $item->producto->descripcion];
                        })->all();
                        if (! $lineas) {
                            throw new \DomainException('La regla debe contener al menos un producto.');
                        }
                        $borrador = app(GuardarBorradorFacturaAction::class);
                        if ($cliente->tipo_identificacion === '07' && BigDecimal::of($borrador->calcularTotales($lineas)['importeTotal'])->isGreaterThan('50')) {
                            throw new \DomainException('Para importes mayores a 50 debe identificar al comprador.');
                        }
                        $factura = $borrador->ejecutar(null, $actual->empresa_id, $usuarioId, [
                            'fecha_emision' => Carbon::now('America/Guayaquil')->toDateString(),
                            'tipo_identificacion_comprador' => $cliente->tipo_identificacion,
                            'identificacion_comprador' => $cliente->identificacion,
                            'razon_social_comprador' => $cliente->razon_social,
                            'direccion_comprador' => $cliente->direccion,
                            'telefono_comprador' => $cliente->telefono,
                            'correo_comprador' => $cliente->correo,
                            'forma_pago' => (int) $actual->forma_pago,
                            'tiempo_plazo' => $actual->tiempo_plazo,
                            'unidad_tiempo' => $actual->tiempo_plazo !== null ? $actual->unidad_tiempo : null,
                        ], $lineas);
                        if ($actual->tipo_emision === 'automatica') {
                            app(ConfirmarFacturaAction::class)->ejecutar($factura);
                        }
                        $actual->ejecuciones()->updateOrCreate($clave, [
                            'factura_id' => $factura->id, 'estado' => 'exitoso', 'modo_emision' => $actual->tipo_emision,
                            'error_mensaje' => null, 'ejecutado_at' => now(),
                        ]);
                    });

                    return 'exitosos';
                } catch (\Throwable $e) {
                    report($e);
                    $actual->ejecuciones()->updateOrCreate($clave, [
                        'factura_id' => null, 'estado' => 'fallido', 'modo_emision' => $actual->tipo_emision,
                        'error_mensaje' => mb_substr($e->getMessage(), 0, 2000), 'ejecutado_at' => now(),
                    ]);

                    return 'fallidos';
                }
            });
            $resumen[$estado]++;
            $resumen['procesados']++;
        }
        if ($regla->frecuencia === 'unica') {
            $regla->update(['activo' => false]);
        }

        return $resumen;
    }

    public static function reglasCliente(string $tipo): array
    {
        return [
            'tipo_identificacion' => ['required', 'in:04,05,06,07'],
            'identificacion' => ['required', 'string', 'max:20', match ($tipo) {
                '04' => new RucEcuatoriano,
                '05' => new CedulaEcuatoriana,
                '07' => 'in:9999999999999',
                default => 'max:20',
            }],
            'razon_social' => ['required', 'string', 'max:300'],
            'direccion' => ['required', 'string', 'max:300'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['required', 'email', 'max:100'],
        ];
    }
}
