<?php

namespace App\Livewire\Facturas;

use App\Actions\Sri\ConfirmarFacturaAction;
use App\Models\Factura;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class FacturaIndex extends Component
{
    use WithPagination;

    public string $filtroEstado = '';

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
            return __('Tu empresa todavía no tiene un certificado de firma electrónica configurado. Pedile al admin de tu empresa que lo suba desde "Mi empresa" antes de confirmar facturas.');
        }

        if ($e instanceof \RuntimeException && str_contains($e->getMessage(), 'No se pudo leer el certificado')) {
            return __('No se pudo leer el certificado de firma electrónica de tu empresa: la contraseña es inválida o el archivo está dañado. Pedile al admin de tu empresa que lo vuelva a subir desde "Mi empresa".');
        }

        return __('No se pudo confirmar la factura. Intentá de nuevo en unos minutos; si el problema sigue, avisale al administrador.');
    }

    public function anularBorrador(int $facturaId): void
    {
        $factura = $this->facturaDeMiEmpresa($facturaId);

        $this->authorize('anularBorrador', $factura);

        $factura->update(['estado' => Factura::ESTADO_ANULADA]);
    }

    private function facturaDeMiEmpresa(int $facturaId): Factura
    {
        return Factura::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->findOrFail($facturaId);
    }

    public function render()
    {
        $facturas = Factura::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->when($this->filtroEstado, fn ($query) => $query->where('estado', $this->filtroEstado))
            ->latest()
            ->paginate(15);

        $todasDeLaEmpresa = Factura::query()->where('empresa_id', auth()->user()->empresa_id);

        $stats = [
            'total' => (clone $todasDeLaEmpresa)->count(),
            'autorizadas' => (clone $todasDeLaEmpresa)->where('estado', Factura::ESTADO_AUTORIZADA)->count(),
            'pendientes' => (clone $todasDeLaEmpresa)->whereIn('estado', [Factura::ESTADO_BORRADOR, Factura::ESTADO_ENVIADA, Factura::ESTADO_DEVUELTA])->count(),
            'anuladas' => (clone $todasDeLaEmpresa)->whereIn('estado', [Factura::ESTADO_ANULADA, Factura::ESTADO_RECHAZADA])->count(),
        ];

        return view('livewire.facturas.factura-index', ['facturas' => $facturas, 'stats' => $stats]);
    }
}
