<?php

namespace App\Livewire\Productos;

use App\Models\Producto;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ProductoIndex extends Component
{
    use WithPagination;

    public string $buscar = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Producto::class);
    }

    public function eliminar(int $productoId): void
    {
        $producto = Producto::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->findOrFail($productoId);

        $this->authorize('delete', $producto);

        if ($producto->reglasFacturacion()->exists()) {
            session()->flash('status', 'El producto está asociado a reglas periódicas. Retíralo de las reglas antes de eliminarlo.');
            return;
        }

        $producto->delete();
    }

    public function render()
    {
        $productos = Producto::query()
            ->withCount(['reglasFacturacion' => fn ($q) => $q->where('reglas_facturacion.empresa_id', auth()->user()->empresa_id)])
            ->where('empresa_id', auth()->user()->empresa_id)
            ->when($this->buscar, fn ($query) => $query->where(fn ($q) => $q
                ->where('codigo_principal', 'like', "%{$this->buscar}%")
                ->orWhere('descripcion', 'like', "%{$this->buscar}%")
            ))
            ->orderBy('descripcion')
            ->paginate(15);

        return view('livewire.productos.producto-index', ['productos' => $productos]);
    }
}
