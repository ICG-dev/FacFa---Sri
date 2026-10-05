<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Services\Sri\SriConsultaComprobanteClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;







class ConsultarEstadoAnulacionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Factura $factura) {}

    public function handle(SriConsultaComprobanteClient $cliente): void
    {
        if ($this->factura->fresh()->estado !== Factura::ESTADO_PENDIENTE_ANULAR) {
            return;
        }

        $estado = $cliente->consultarEstadoAutorizacion($this->factura->clave_acceso, $this->factura->empresa, $this->factura->ambienteEmision());

        if ($estado === 'ANULADO') {
            $this->factura->update([
                'estado' => Factura::ESTADO_ANULADA,
                'fecha_anulacion' => now(),
            ]);

            return;
        }

        if ($estado === 'AUTORIZADO') {
            
            
            $this->factura->update([
                'estado' => Factura::ESTADO_AUTORIZADA,
                'mensajes_error' => [[
                    'identificador' => null,
                    'mensaje' => 'La solicitud de anulación no se completó en el portal SRI (el comprobante sigue AUTORIZADO). Revisar el trámite manualmente si se esperaba una anulación.',
                    'tipo' => 'ADVERTENCIA',
                ]],
            ]);
        }

        
        
    }
}
