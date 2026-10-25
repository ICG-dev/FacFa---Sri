<?php

namespace App\Jobs;

use App\Actions\ReglasFacturacion\EjecutarReglaFacturacionAction;
use App\Models\ReglaFacturacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcesarReglaFacturacionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $reglaId,
        public int $empresaId,
        public string $ciclo,
        public ?int $usuarioId = null,
        public bool $programada = false,
        public ?string $clienteIdentificacion = null,
        public ?string $periodoReintento = null,
    ) {
        $this->onConnection('redis')->onQueue('default')->afterCommit();
    }

    public function handle(EjecutarReglaFacturacionAction $accion): void
    {
        $regla = ReglaFacturacion::where('empresa_id', $this->empresaId)->find($this->reglaId);
        if ($regla) {
            $accion->ejecutar($regla, $this->usuarioId, $this->ciclo, $this->programada, $this->clienteIdentificacion, $this->periodoReintento);
        }
    }
}
