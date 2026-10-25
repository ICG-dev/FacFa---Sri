<?php

namespace App\Livewire\ReglasFacturacion;

use App\Actions\ReglasFacturacion\EjecutarReglaFacturacionAction;
use App\Models\Factura;
use App\Models\FormaPago;
use App\Models\Producto;
use App\Models\ReglaFacturacion;
use App\Models\TarifaIva;
use App\Services\Sri\FacturaCalculoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ReglaFacturacionForm extends Component
{
    public ?ReglaFacturacion $regla = null;

    public string $nombre = '';

    public string $descripcion = '';

    public string $tipo_emision = 'revision';

    public string $frecuencia = 'mensual';

    public $dia_corte = 1;

    public string $fecha_inicio = '';

    public ?string $fecha_fin = null;

    public string $forma_pago = '01';

    public $tiempo_plazo = null;

    public string $unidad_tiempo = 'dias';

    public bool $activo = true;

    public array $productos = [];

    public array $clientes = [];

    public string $buscarProducto = '';

    public string $buscarCliente = '';

    public function mount(?ReglaFacturacion $regla = null): void
    {
        if ($regla?->exists) {
            $this->authorize('update', $regla);
            $this->regla = $regla;
            foreach (['nombre', 'tipo_emision', 'frecuencia', 'dia_corte', 'forma_pago', 'tiempo_plazo', 'unidad_tiempo', 'activo'] as $campo) {
                $this->$campo = $regla->$campo;
            }
            $this->descripcion = $regla->descripcion ?? '';
            $this->fecha_inicio = $regla->fecha_inicio->toDateString();
            $this->fecha_fin = $regla->fecha_fin?->toDateString();
            $this->productos = $regla->productos()->with('producto')->get()->map(fn ($item) => [
                ...$item->only(['producto_id', 'cantidad', 'precio_unitario', 'descuento', 'codigo_porcentaje']),
                'descripcion' => $item->producto->descripcion,
            ])->all();
            $this->clientes = $regla->clientes->map(fn ($c) => $c->only(['tipo_identificacion', 'identificacion', 'razon_social', 'direccion', 'telefono', 'correo', 'activo']))->all();
        } else {
            $this->authorize('create', ReglaFacturacion::class);
            $this->fecha_inicio = now('America/Guayaquil')->toDateString();
        }
    }

    public function agregarProducto(int $id): void
    {
        $producto = Producto::where('empresa_id', auth()->user()->empresa_id)->findOrFail($id);
        $this->productos[] = ['producto_id' => $producto->id, 'descripcion' => $producto->descripcion, 'cantidad' => '1', 'precio_unitario' => $producto->precio_unitario, 'descuento' => '0', 'codigo_porcentaje' => $producto->codigo_porcentaje];
        $this->buscarProducto = '';
    }

    public function quitarProducto(int $indice): void
    {
        unset($this->productos[$indice]);
        $this->productos = array_values($this->productos);
    }

    public function agregarCliente(): void
    {
        $this->clientes[] = ['tipo_identificacion' => '05', 'identificacion' => '', 'razon_social' => '', 'direccion' => '', 'telefono' => '', 'correo' => '', 'activo' => true];
    }

    public function seleccionarCliente(int $facturaId): void
    {
        $factura = Factura::where('empresa_id', auth()->user()->empresa_id)->findOrFail($facturaId);
        $this->clientes[] = ['tipo_identificacion' => $factura->tipo_identificacion_comprador, 'identificacion' => $factura->identificacion_comprador, 'razon_social' => $factura->razon_social_comprador, 'direccion' => $factura->direccion_comprador, 'telefono' => $factura->telefono_comprador, 'correo' => $factura->correo_comprador, 'activo' => true];
        $this->buscarCliente = '';
    }

    public function quitarCliente(int $indice): void
    {
        unset($this->clientes[$indice]);
        $this->clientes = array_values($this->clientes);
    }

    public function getTotalesProperty(): array
    {
        return app(FacturaCalculoService::class)->calcular($this->productos, previsualizar: true)['totales'];
    }

    public function guardar(): void
    {
        $this->authorize($this->regla ? 'update' : 'create', $this->regla ?? ReglaFacturacion::class);
        $empresaId = auth()->user()->empresa_id;
        $reglas = [
            'nombre' => ['required', 'string', 'max:150'], 'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo_emision' => ['required', 'in:revision,automatica'], 'frecuencia' => ['required', 'in:unica,diaria,semanal,mensual,anual'],
            'dia_corte' => [in_array($this->frecuencia, ['mensual', 'semanal']) ? 'required' : 'nullable', 'integer', 'min:1', 'max:'.($this->frecuencia === 'semanal' ? 7 : 28)],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'], 'fecha_fin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'forma_pago' => ['required', 'exists:formas_pago,codigo'], 'tiempo_plazo' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'unidad_tiempo' => ['required', 'in:dias'], 'activo' => ['boolean'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('empresa_id', $empresaId)],
            'productos.*.cantidad' => ['required', 'numeric', 'min:0.000001', 'max:999999.999999', 'decimal:0,6'],
            'productos.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:99999999.999999', 'decimal:0,6'],
            'productos.*.descuento' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'productos.*.codigo_porcentaje' => ['required', Rule::exists('tarifas_iva', 'codigo_porcentaje')->where('activo', true)],
            'clientes' => ['required', 'array', 'min:1'], 'clientes.*.identificacion' => ['distinct'],
            'clientes.*.activo' => ['required', 'boolean'],
        ];
        foreach ($this->clientes as $i => $cliente) {
            foreach (EjecutarReglaFacturacionAction::reglasCliente((string) ($cliente['tipo_identificacion'] ?? '')) as $campo => $validaciones) {
                $reglas["clientes.$i.$campo"] = $validaciones;
            }
        }
        $this->withValidator(function ($validator) {
            $validator->after(function ($validator) {
                foreach ($this->productos as $i => $producto) {
                    foreach (app(FacturaCalculoService::class)->erroresLinea($producto) as $campo => $mensaje) {
                        $validator->errors()->add("productos.$i.$campo", $mensaje);
                    }
                }
            });
        });
        $datos = $this->validate($reglas);
        $productos = $datos['productos'];
        $clientes = $datos['clientes'];
        unset($datos['productos'], $datos['clientes']);
        $datos['fecha_fin'] = $datos['fecha_fin'] ?: null;
        $datos['tiempo_plazo'] = $datos['tiempo_plazo'] === '' ? null : $datos['tiempo_plazo'];
        if (! in_array($datos['frecuencia'], ['mensual', 'semanal'])) {
            $datos['dia_corte'] = null;
        }
        $regla = DB::transaction(function () use ($datos, $productos, $clientes, $empresaId) {
            $regla = $this->regla ? ReglaFacturacion::where('empresa_id', $empresaId)->lockForUpdate()->findOrFail($this->regla->id) : new ReglaFacturacion(['empresa_id' => $empresaId, 'user_id' => auth()->id()]);
            if ($regla->exists && $regla->frecuencia !== $datos['frecuencia'] && $regla->ejecuciones()->exists()) {
                throw ValidationException::withMessages(['frecuencia' => 'Crea otra regla para cambiar la frecuencia de una regla con historial.']);
            }
            $regla->fill($datos);
            if (! $regla->exists || $regla->isDirty(['fecha_inicio', 'frecuencia', 'dia_corte'])) {
                $regla->proxima_ejecucion_at = $regla->primerCiclo();
                while ($regla->exists && $regla->proxima_ejecucion_at->lt(now('America/Guayaquil')->startOfDay()) && $regla->frecuencia !== 'unica') {
                    $regla->proxima_ejecucion_at = $regla->calcularProximoCiclo();
                }
            }
            $regla->save();
            $regla->productos()->delete();
            foreach ($productos as $producto) {
                $tarifa = TarifaIva::activas()->where('codigo_porcentaje', $producto['codigo_porcentaje'])->firstOrFail();
                $regla->productos()->create([...$producto, 'codigo_impuesto' => 2, 'tarifa' => $tarifa->porcentaje]);
            }
            $regla->clientes()->delete();
            $regla->clientes()->createMany($clientes);

            return $regla;
        });
        session()->flash('status', 'Regla guardada correctamente.');
        $this->redirectRoute('reglas-facturacion.show', $regla, navigate: true);
    }

    public function render()
    {
        $empresaId = auth()->user()->empresa_id;
        $catalogo = Producto::where('empresa_id', $empresaId)
            ->where(fn ($q) => $q->where('descripcion', 'like', '%'.$this->buscarProducto.'%')->orWhere('codigo_principal', 'like', '%'.$this->buscarProducto.'%'))
            ->orderBy('descripcion')->limit(10)->get();
        $anteriores = $this->buscarCliente === '' ? collect() : Factura::where('empresa_id', $empresaId)
            ->where(fn ($q) => $q->where('identificacion_comprador', 'like', '%'.$this->buscarCliente.'%')->orWhere('razon_social_comprador', 'like', '%'.$this->buscarCliente.'%'))
            ->latest('id')->limit(20)->get()->unique('identificacion_comprador')->take(6);

        return view('livewire.reglas-facturacion.regla-facturacion-form', ['catalogo' => $catalogo, 'anteriores' => $anteriores, 'formasPago' => FormaPago::orderBy('codigo')->get(), 'tarifas' => TarifaIva::activas()->get()]);
    }
}
