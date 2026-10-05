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

        $producto->delete();
    }

    public function render()
    {
        $productos = Producto::query()
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
