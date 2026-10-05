<?php

namespace App\Livewire\Facturas;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\FormaPago;
use App\Models\Producto;
use App\Models\TipoIdentificacion;
use Livewire\Component;

class FacturaForm extends Component
{
    public ?Factura $factura = null;

    public string $fecha_emision;

    public string $tipo_identificacion_comprador = '05';

    public string $identificacion_comprador = '';

    public string $razon_social_comprador = '';

    public string $direccion_comprador = '';

    public string $telefono_comprador = '';

    public string $correo_comprador = '';

    public string $guia_remision = '';

    public bool $factura_comercial_negociable = false;

    public int $forma_pago = 1;

    
    public array $lineas = [];

    
    public array $sugerenciasProductos = [];

    public function mount(?Factura $factura = null): void
    {
        
        
        
        
        if ($factura && $factura->exists) {
            $this->authorize('update', $factura);

            $this->factura = $factura;
            $this->fecha_emision = $factura->fecha_emision->format('Y-m-d');
            $this->tipo_identificacion_comprador = $factura->tipo_identificacion_comprador;
            $this->identificacion_comprador = $factura->identificacion_comprador;
            $this->razon_social_comprador = $factura->razon_social_comprador;
            $this->direccion_comprador = $factura->direccion_comprador ?? '';
            $this->telefono_comprador = $factura->telefono_comprador ?? '';
            $this->correo_comprador = $factura->correo_comprador ?? '';
            $this->guia_remision = $factura->guia_remision ?? '';
            $this->factura_comercial_negociable = $factura->factura_comercial_negociable ?? false;
            $this->forma_pago = $factura->forma_pago ?? 1;

            $this->lineas = $factura->detalles->map(fn (FacturaDetalle $detalle) => [
                'codigo_principal' => $detalle->codigo_principal,
                'codigo_auxiliar' => $detalle->codigo_auxiliar ?? '',
                'descripcion' => $detalle->descripcion,
                'detalle_adicional' => $detalle->detalle_adicional ?? '',
                'cantidad' => (float) $detalle->cantidad,
                'precio_unitario' => (float) $detalle->precio_unitario,
                'subsidio' => (float) $detalle->subsidio,
                'descuento' => (float) $detalle->descuento,
                'tarifa' => (float) $detalle->tarifa,
            ])->all();

            return;
        }

        $this->authorize('create', Factura::class);
        $this->fecha_emision = now()->toDateString();
        $this->agregarLinea();
    }

    public function agregarLinea(): void
    {
        $this->lineas[] = [
            'codigo_principal' => '',
            'codigo_auxiliar' => '',
            'descripcion' => '',
            'detalle_adicional' => '',
            'cantidad' => 1.00,
            'precio_unitario' => 0,
            'subsidio' => 0,
            'descuento' => 0,
            'tarifa' => 15,
        ];
    }

    public function quitarLinea(int $indice): void
    {
        unset($this->lineas[$indice]);
        $this->lineas = array_values($this->lineas);
        unset($this->sugerenciasProductos[$indice]);
    }

    






    public function updated(string $name): void
    {
        if (! preg_match('/^lineas\.(\d+)\.codigo_principal$/', $name, $coincidencia)) {
            return;
        }

        $indice = (int) $coincidencia[1];
        $texto = trim($this->lineas[$indice]['codigo_principal'] ?? '');

        if (mb_strlen($texto) < 2) {
            unset($this->sugerenciasProductos[$indice]);

            return;
        }

        $texto = addcslashes($texto, '%_\\');

        $this->sugerenciasProductos[$indice] = Producto::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->where(fn ($query) => $query
                ->whereRaw("codigo_principal like ? ESCAPE '\\'", ["%{$texto}%"])
                ->orWhereRaw("descripcion like ? ESCAPE '\\'", ["%{$texto}%"]))
            ->orderBy('descripcion')
            ->limit(6)
            ->get();
    }

    public function seleccionarProducto(int $indice, int $productoId): void
    {
        $producto = Producto::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->findOrFail($productoId);

        $this->lineas[$indice]['codigo_principal'] = $producto->codigo_principal;
        $this->lineas[$indice]['descripcion'] = $producto->descripcion;
        $this->lineas[$indice]['precio_unitario'] = (float) $producto->precio_unitario;
        $this->lineas[$indice]['tarifa'] = (float) $producto->tarifa;

        unset($this->sugerenciasProductos[$indice]);
    }

    public function cerrarSugerencias(int $indice): void
    {
        unset($this->sugerenciasProductos[$indice]);
    }

    public function getTotalesProperty(): array
    {
        
        $lineas = array_map(function ($linea) {
            if (! in_array((string) ($linea['tarifa'] ?? ''), ['0', '5', '8', '15'], true)) {
                $linea['tarifa'] = 0;
            }

            return $linea;
        }, $this->lineas);

        return app(GuardarBorradorFacturaAction::class)->calcularTotales($lineas);
    }

    public function getFormasPagoProperty()
    {
        return FormaPago::query()->orderBy('codigo')->get();
    }

    public function getTiposIdentificacionProperty()
    {
        return TipoIdentificacion::query()->orderBy('codigo')->get();
    }

    public function guardar(GuardarBorradorFacturaAction $accion): void
    {
        $datos = $this->validate([
            'fecha_emision' => ['required', 'date'],
            'tipo_identificacion_comprador' => ['required', 'string', 'exists:tipos_identificacion,codigo'],
            'identificacion_comprador' => ['required', 'string', 'max:20', match ($this->tipo_identificacion_comprador) {
                '04' => 'digits:13',
                '05' => 'digits:10',
                '07' => 'in:'.config('sri.identificacion_consumidor_final'),
                default => 'max:20',
            }],
            'razon_social_comprador' => ['required', 'string', 'max:300'],
            'direccion_comprador' => ['nullable', 'string', 'max:300'],
            'telefono_comprador' => ['nullable', 'string', 'max:20'],
            'correo_comprador' => ['required', 'email', 'max:150'],
            'guia_remision' => ['nullable', 'string', 'max:20'],
            'factura_comercial_negociable' => ['boolean'],
            'forma_pago' => ['required', 'integer', 'exists:formas_pago,codigo'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.codigo_principal' => ['required', 'string', 'max:25'],
            'lineas.*.codigo_auxiliar' => ['nullable', 'string', 'max:25'],
            'lineas.*.descripcion' => ['required', 'string', 'max:300'],
            'lineas.*.detalle_adicional' => ['nullable', 'string', 'max:300'],
            'lineas.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'lineas.*.subsidio' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                $linea = $this->lineas[explode('.', $attribute)[1]];
                if ((float) $value > (float) ($linea['precio_unitario'] ?? 0)) {
                    $fail('El subsidio no puede superar el precio unitario.');
                }
            }],
            'lineas.*.descuento' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                $linea = $this->lineas[explode('.', $attribute)[1]];
                $subtotal = (float) ($linea['cantidad'] ?? 0) * ((float) ($linea['precio_unitario'] ?? 0) - (float) ($linea['subsidio'] ?? 0));
                if ((float) $value > $subtotal) {
                    $fail('El descuento no puede superar el subtotal de la línea.');
                }
            }],
            'lineas.*.tarifa' => ['required', 'numeric', 'in:0,5,8,15'],
        ], [], [
            'fecha_emision' => 'fecha de emisión',
            'razon_social_comprador' => 'razón social del comprador',
            'identificacion_comprador' => 'identificación del comprador',
            'correo_comprador' => 'correo electrónico del comprador',
        ]);

        if ($datos['tipo_identificacion_comprador'] === '07' && $accion->calcularTotales($datos['lineas'])['importeTotal'] > 50) {
            $this->addError('identificacion_comprador', 'Para importes mayores a 50 debe identificar al comprador.');

            return;
        }

        if ($this->factura) {
            $this->authorize('update', $this->factura);
        } else {
            $this->authorize('create', Factura::class);
        }

        $this->factura = $accion->ejecutar(
            $this->factura,
            auth()->user()->empresa_id,
            auth()->id(),
            [
                'fecha_emision' => $datos['fecha_emision'],
                'tipo_identificacion_comprador' => $datos['tipo_identificacion_comprador'],
                'identificacion_comprador' => $datos['identificacion_comprador'],
                'razon_social_comprador' => $datos['razon_social_comprador'],
                'direccion_comprador' => $datos['direccion_comprador'],
                'telefono_comprador' => $datos['telefono_comprador'],
                'correo_comprador' => $datos['correo_comprador'],
                'guia_remision' => $datos['guia_remision'],
                'factura_comercial_negociable' => $datos['factura_comercial_negociable'],
                'forma_pago' => $datos['forma_pago'],
            ],
            $datos['lineas'],
        );

        $this->redirectRoute('facturas.show', $this->factura, navigate: true);
    }

    public function render()
    {
        return view('livewire.facturas.factura-form');
    }
}
