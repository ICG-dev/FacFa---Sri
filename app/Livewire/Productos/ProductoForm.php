<?php

namespace App\Livewire\Productos;

use App\Actions\Productos\GuardarProductoAction;
use App\Models\Producto;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductoForm extends Component
{
    public ?Producto $producto = null;

    public string $codigo_principal = '';

    public string $descripcion = '';

    public string $precio_unitario = '';

    public string $tarifa = '15';

    public function mount(?Producto $producto = null): void
    {
        
        
        if ($producto && $producto->exists) {
            $this->authorize('update', $producto);

            $this->producto = $producto;
            $this->codigo_principal = $producto->codigo_principal;
            $this->descripcion = $producto->descripcion;
            $this->precio_unitario = (string) $producto->precio_unitario;
            $this->tarifa = (string) $producto->tarifa;

            return;
        }

        $this->authorize('create', Producto::class);
    }

    public function guardar(GuardarProductoAction $accion): void
    {
        $empresaId = auth()->user()->empresa_id;

        $datos = $this->validate([
            'codigo_principal' => [
                'required', 'string', 'max:25',
                Rule::unique('productos', 'codigo_principal')
                    ->where('empresa_id', $empresaId)
                    ->ignore($this->producto?->id),
            ],
            'descripcion' => ['required', 'string', 'max:300'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'tarifa' => ['required', 'numeric', 'in:0,15'],
        ]);

        if ($this->producto) {
            $this->authorize('update', $this->producto);
        } else {
            $this->authorize('create', Producto::class);
        }

        $accion->ejecutar($this->producto, $empresaId, $datos);

        session()->flash('status', __('Producto guardado correctamente.'));

        $this->redirectRoute('productos.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.productos.producto-form');
    }
}
