<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Services\Sri\SriRecepcionClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarComprobanteRecepcionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public Factura $factura) {}

    public function handle(SriRecepcionClient $cliente): void
    {
        $this->factura = $this->factura->fresh();

        if ($this->factura->estado !== Factura::ESTADO_ENVIADA) {
            return;
        }

        $respuesta = $cliente->enviar($this->factura->xml_firmado, $this->factura->empresa, $this->factura->ambienteEmision());

        $claveRegistrada = $respuesta['estado'] === 'DEVUELTA'
            && collect($respuesta['mensajes'])->contains(fn ($mensaje) => (string) ($mensaje['identificador'] ?? '') === '43');

        if ($respuesta['estado'] === 'RECIBIDA' || $claveRegistrada) {
            ConsultarAutorizacionJob::dispatch($this->factura)
                ->onQueue('sri')
                ->delay(now()->addSeconds(config('sri.autorizacion_delay_inicial')));

            return;
        }

        
        
        
        $this->factura->update([
            'estado' => Factura::ESTADO_DEVUELTA,
            'mensajes_error' => $respuesta['mensajes'],
        ]);

        Log::warning('Factura devuelta por el WS de Recepción del SRI.', [
            'factura_id' => $this->factura->id,
            'clave_acceso' => $this->factura->clave_acceso,
            'mensajes' => $respuesta['mensajes'],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->factura->newQuery()
            ->whereKey($this->factura->id)
            ->where('estado', Factura::ESTADO_ENVIADA)
            ->update([
                'estado' => Factura::ESTADO_DEVUELTA,
                'mensajes_error' => [[
                    'identificador' => null,
                    'mensaje' => 'No se pudo completar la recepción del SRI. Revise la factura antes de reenviarla.',
                    'tipo' => 'ADVERTENCIA',
                ]],
            ]);

        Log::warning('Se agotaron los intentos de recepción del SRI.', [
            'factura_id' => $this->factura->id,
            'exception' => $e,
        ]);
    }
}
