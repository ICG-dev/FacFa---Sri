<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Services\Sri\SriAutorizacionClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ConsultarAutorizacionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Factura $factura) {}

    public function handle(SriAutorizacionClient $cliente): void
    {
        
        
        if ($this->factura->fresh()->estado !== Factura::ESTADO_ENVIADA) {
            return;
        }

        try {
            $respuesta = $cliente->consultar($this->factura->clave_acceso, $this->factura->empresa, $this->factura->ambienteEmision());
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar la autorización del SRI.', [
                'factura_id' => $this->factura->id,
                'exception' => $e,
            ]);
            $this->reintentarOExpirar();

            return;
        }

        match ($respuesta['estado']) {
            'AUTORIZADO' => $this->marcarAutorizada($respuesta),
            'NO AUTORIZADO', 'RECHAZADO', 'RECHAZADA' => $this->marcarRechazada($respuesta),
            default => $this->reintentarOExpirar(),
        };
    }

    private function marcarAutorizada(array $respuesta): void
    {
        $this->factura->update([
            'estado' => Factura::ESTADO_AUTORIZADA,
            
            
            'numero_autorizacion' => $respuesta['numeroAutorizacion'] ?? $this->factura->clave_acceso,
            'fecha_autorizacion' => $respuesta['fechaAutorizacion'] ?? now(),
            'xml_autorizado' => $respuesta['xmlAutorizado'],
            'mensajes_error' => $respuesta['mensajes'],
        ]);
    }

    private function marcarRechazada(array $respuesta): void
    {
        $this->factura->update([
            'estado' => Factura::ESTADO_RECHAZADA,
            'mensajes_error' => $respuesta['mensajes'],
        ]);
    }

    private function reintentarOExpirar(): void
    {
        $horasTranscurridas = $this->factura->enviado_at->diffInHours(now());

        if ($horasTranscurridas >= config('sri.autorizacion_max_horas')) {
            $this->factura->update([
                'mensajes_error' => [[
                    'identificador' => null,
                    'mensaje' => 'La autorización no se resolvió dentro del plazo máximo documentado por el SRI ('.config('sri.autorizacion_max_horas').'h). Requiere revisión manual.',
                    'tipo' => 'ADVERTENCIA',
                ]],
            ]);

            Log::warning('Factura sin autorización tras el plazo máximo del SRI.', [
                'factura_id' => $this->factura->id,
                'clave_acceso' => $this->factura->clave_acceso,
            ]);

            return;
        }

        self::dispatch($this->factura)
            ->onQueue('sri')
            ->delay(now()->addSeconds(config('sri.autorizacion_delay_reintento')));
    }
}
