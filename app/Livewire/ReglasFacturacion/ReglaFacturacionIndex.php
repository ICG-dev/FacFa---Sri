<?php

namespace App\Livewire\ReglasFacturacion;

use App\Jobs\ProcesarReglaFacturacionJob;
use App\Models\ReglaFacturacion;
use Livewire\Component;
use Livewire\WithPagination;

class ReglaFacturacionIndex extends Component
{
    use WithPagination;

    public string $buscar = '';

    public string $estado = '';

    public string $modo = '';

    public string $frecuencia = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ReglaFacturacion::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function toggleActivo(int $id): void
    {
        $regla = $this->regla($id);
        $this->authorize('update', $regla);
        if (! $regla->activo && $regla->frecuencia !== 'unica') {
            $hoy = now('America/Guayaquil')->startOfDay();
            while ($regla->proxima_ejecucion_at->lt($hoy)) {
                $regla->proxima_ejecucion_at = $regla->calcularProximoCiclo();
            }
        }
        $regla->activo = ! $regla->activo;
        $regla->save();
    }

    public function ejecutar(int $id): void
    {
        $regla = $this->regla($id);
        $this->authorize('ejecutar', $regla);
        ProcesarReglaFacturacionJob::dispatch($regla->id, $regla->empresa_id, now('America/Guayaquil')->toDateString(), auth()->id());
        session()->flash('status', 'Ejecución enviada a la cola. Consulta el historial para ver el resultado.');
    }

    public function eliminar(int $id): void
    {
        $regla = $this->regla($id);
        $this->authorize('delete', $regla);
        if ($regla->ejecuciones()->exists()) {
            $this->addError('regla', 'Esta regla tiene historial. Puedes pausarla para conservar la auditoría.');

            return;
        }
        $regla->delete();
    }

    private function regla(int $id): ReglaFacturacion
    {
        return ReglaFacturacion::where('empresa_id', auth()->user()->empresa_id)->findOrFail($id);
    }

    public function render()
    {
        $this->authorize('viewAny', ReglaFacturacion::class);
        $reglas = ReglaFacturacion::where('empresa_id', auth()->user()->empresa_id)
            ->when($this->buscar !== '', fn ($q) => $q->where('nombre', 'like', '%'.$this->buscar.'%'))
            ->when($this->estado !== '', fn ($q) => $q->where('activo', $this->estado === 'activa'))
            ->when($this->modo !== '', fn ($q) => $q->where('tipo_emision', $this->modo))
            ->when($this->frecuencia !== '', fn ($q) => $q->where('frecuencia', $this->frecuencia))
            ->orderByDesc('id')->paginate(15);

        return view('livewire.reglas-facturacion.regla-facturacion-index', compact('reglas'));
    }
}
