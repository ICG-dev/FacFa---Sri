<?php

namespace App\Livewire\ReglasFacturacion;

use App\Jobs\ProcesarReglaFacturacionJob;
use App\Models\ReglaFacturacion;
use Livewire\Component;
use Livewire\WithPagination;

class ReglaFacturacionShow extends Component
{
    use WithPagination;

    public ReglaFacturacion $regla;

    public function mount(ReglaFacturacion $regla): void
    {
        $this->authorize('view', $regla);
        $this->regla = $regla;
    }

    public function ejecutar(): void
    {
        $this->authorize('ejecutar', $this->regla);
        ProcesarReglaFacturacionJob::dispatch($this->regla->id, $this->regla->empresa_id, now('America/Guayaquil')->toDateString(), auth()->id());
        session()->flash('status', 'Ejecución enviada a la cola.');
    }

    public function reintentar(int $id): void
    {
        $this->authorize('ejecutar', $this->regla);
        $ejecucion = $this->regla->ejecuciones()->where('estado', 'fallido')->findOrFail($id);
        // El reintento conserva el período incluso en reglas únicas ya finalizadas.
        ProcesarReglaFacturacionJob::dispatch($this->regla->id, $this->regla->empresa_id, $this->regla->fecha_inicio->toDateString(), auth()->id(), true, $ejecucion->cliente_identificacion, $ejecucion->periodo);
        session()->flash('status', 'Reintento enviado a la cola para este cliente.');
    }

    public function render()
    {
        $this->authorize('view', $this->regla);
        $this->regla->refresh();
        $ejecuciones = $this->regla->ejecuciones()->with(['factura' => fn ($q) => $q->where('empresa_id', auth()->user()->empresa_id)])->orderByDesc('ejecutado_at')->orderByDesc('id')->paginate(15);

        return view('livewire.reglas-facturacion.regla-facturacion-show', compact('ejecuciones'));
    }
}
