<?php

namespace App\Livewire\Facturas;

use App\Actions\Sri\ConfirmarFacturaAction;
use App\Models\Factura;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class FacturaIndex extends Component
{
    use WithPagination;

    public string $filtroEstado = '';

    public string $busqueda = '';

    #[Validate('nullable|date')]
    public string $fechaDesde = '';

    #[Validate('nullable|date')]
    public string $fechaHasta = '';

    #[Locked]
    public ?int $facturaXmlId = null;

    #[Locked]
    public ?string $xmlModalContenido = null;

    #[Locked]
    public ?string $xmlModalNumero = null;

    public function verXmlModal(int $facturaId): void
    {
        $factura = $this->facturaDeMiEmpresa($facturaId);
        $this->authorize('descargarXml', $factura);

        $xml = $factura->xml_autorizado ?: $factura->xml_firmado ?: $factura->xml_generado;
        abort_unless($xml, 404);

        $this->facturaXmlId = $factura->id;
        $this->xmlModalContenido = $xml;
        $this->xmlModalNumero = sprintf('%03d-%03d-%09d', $factura->establecimiento, $factura->punto_emision, $factura->secuencial);
    }

    public function cerrarXmlModal(): void
    {
        $this->facturaXmlId = null;
        $this->xmlModalContenido = null;
        $this->xmlModalNumero = null;
    }

    public function getFacturaXmlProperty(): ?Factura
    {
        if ($this->facturaXmlId === null) {
            return null;
        }

        $factura = $this->facturaDeMiEmpresa($this->facturaXmlId);
        $this->authorize('descargarXml', $factura);

        return $factura;
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Factura::class);
    }

    public function confirmar(int $facturaId, ConfirmarFacturaAction $accion): void
    {
        $factura = $this->facturaDeMiEmpresa($facturaId);

        $this->authorize('confirmar', $factura);

        try {
            $accion->ejecutar($factura);
        } catch (\DomainException $e) {
            $this->dispatch('alerta-error', mensaje: $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('alerta-error', mensaje: $this->mensajeAmigable($e));

            return;
        }

        session()->flash('status', 'Factura enviada al SRI. El estado se actualizará automáticamente.');
    }

    private function mensajeAmigable(\Throwable $e): string
    {
        if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'no tiene un certificado .p12')) {
            return __('Tu empresa todavía no tiene un certificado de firma electrónica configurado. Pide al administrador de tu empresa que lo suba desde "Mi empresa" antes de confirmar facturas.');
        }

        if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'No se pudo leer el certificado')) {
            return __('No se pudo leer el certificado de firma electrónica de tu empresa: la contraseña es inválida o el archivo está dañado. Pide al administrador de tu empresa que lo vuelva a subir desde "Mi empresa".');
        }

        return __('No se pudo confirmar la factura. Intenta de nuevo en unos minutos; si el problema persiste, avisa al administrador.');
    }

    public function reabrirBorrador(int $facturaId): void
    {
        $factura = $this->facturaDeMiEmpresa($facturaId);
        $this->authorize('reabrirBorrador', $factura);

        $factura->update([
            'estado' => Factura::ESTADO_BORRADOR,
            'mensajes_error' => null,
        ]);

        $this->redirectRoute('facturas.edit', $factura, navigate: true);
    }

    public function anularBorrador(int $facturaId): void
    {
        $factura = $this->facturaDeMiEmpresa($facturaId);

        $this->authorize('anularBorrador', $factura);

        $factura->update(['estado' => Factura::ESTADO_ANULADA]);
    }

    private function fechaValida(string $fecha): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $d !== false && $d->format('Y-m-d') === $fecha;
    }

    private function facturaDeMiEmpresa(int $facturaId): Factura
    {
        return Factura::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->findOrFail($facturaId);
    }

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['busqueda', 'filtroEstado', 'fechaDesde', 'fechaHasta']);
        $this->resetPage();
    }

    public function getFiltrosActivosProperty(): bool
    {
        return $this->busqueda !== '' || $this->filtroEstado !== ''
            || $this->fechaDesde !== '' || $this->fechaHasta !== '';
    }

    public function render()
    {
        $estadosFiltrados = match ($this->filtroEstado) {
            'pendientes' => [Factura::ESTADO_BORRADOR, Factura::ESTADO_ENVIADA],
            'rechazadas' => [Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA],
            'anuladas' => [Factura::ESTADO_ANULADA, Factura::ESTADO_PENDIENTE_ANULAR],
            default => [$this->filtroEstado],
        };

        $facturas = Factura::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->when($this->filtroEstado, fn ($query) => $query->whereIn('estado', $estadosFiltrados))
            ->when(trim($this->busqueda) !== '', function ($query) {
                $termino = trim($this->busqueda);
                $query->where(function ($query) use ($termino) {
                    $like = '%'.addcslashes($termino, '%_\\').'%';

                    $query->whereLike('razon_social_comprador', $like)
                        ->orWhere('identificacion_comprador', 'like', $like)
                        ->orWhere('clave_acceso', 'like', $like);

                    $secuencial = match (true) {
                        (bool) preg_match('/^\d{3}-\d{3}-(\d{9})$/', $termino, $partes) => (int) $partes[1],
                        ctype_digit($termino) && strlen($termino) <= 9 => (int) $termino,
                        default => null,
                    };
                    if ($secuencial !== null) {
                        $query->orWhere('secuencial', $secuencial);
                    }
                });
            })
            ->when($this->fechaValida($this->fechaDesde), fn ($query) => $query->whereDate('fecha_emision', '>=', $this->fechaDesde))
            ->when($this->fechaValida($this->fechaHasta), fn ($query) => $query->whereDate('fecha_emision', '<=', $this->fechaHasta))
            ->latest()
            ->paginate(15);

        $todasDeLaEmpresa = Factura::query()->where('empresa_id', auth()->user()->empresa_id);

        $stats = [
            'total' => (clone $todasDeLaEmpresa)->count(),
            'autorizadas' => (clone $todasDeLaEmpresa)->where('estado', Factura::ESTADO_AUTORIZADA)->count(),
            'pendientes' => (clone $todasDeLaEmpresa)->whereIn('estado', [Factura::ESTADO_BORRADOR, Factura::ESTADO_ENVIADA])->count(),
            'rechazadas' => (clone $todasDeLaEmpresa)->whereIn('estado', [Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA])->count(),
            'anuladas' => (clone $todasDeLaEmpresa)->whereIn('estado', [Factura::ESTADO_ANULADA, Factura::ESTADO_PENDIENTE_ANULAR])->count(),
        ];

        return view('livewire.facturas.factura-index', ['facturas' => $facturas, 'stats' => $stats]);
    }
}
