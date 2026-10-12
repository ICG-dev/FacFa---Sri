<?php

namespace App\Livewire\Facturas;

use App\Models\Factura;
use App\Services\Sri\AnulacionElegibilidadService;
use Livewire\Component;

class FacturaShow extends Component
{
    public Factura $factura;

    public bool $mostrarXmlModal = false;

    public function verXmlModal(): void
    {
        $this->factura->refresh();
        $this->authorize('descargarXml', $this->factura);
        abort_unless($this->xmlContenido, 404);

        $this->mostrarXmlModal = true;
    }

    public function cerrarXmlModal(): void
    {
        $this->mostrarXmlModal = false;
    }

    public function getXmlContenidoProperty(): ?string
    {
        $this->authorize('descargarXml', $this->factura);

        return $this->factura->xml_autorizado ?: $this->factura->xml_firmado ?: $this->factura->xml_generado;
    }

    public function mount(Factura $factura): void
    {
        $this->authorize('view', $factura);

        $this->factura = $factura->load('detalles', 'usuario');
    }

    





    public function refrescar(): void
    {
        $this->factura->refresh();
    }

    public function reabrirBorrador(): void
    {
        $this->authorize('reabrirBorrador', $this->factura);

        $this->factura->update([
            'estado' => Factura::ESTADO_BORRADOR,
            'mensajes_error' => null,
        ]);

        $this->redirectRoute('facturas.edit', $this->factura, navigate: true);
    }

    public function solicitarAnulacion(): void
    {
        $this->authorize('solicitarAnulacion', $this->factura);

        $this->factura->update([
            'estado' => Factura::ESTADO_PENDIENTE_ANULAR,
        ]);
    }

    public function getViaAnulacionProperty(): ?string
    {
        if ($this->factura->estado !== Factura::ESTADO_AUTORIZADA) {
            return null;
        }

        return app(AnulacionElegibilidadService::class)->via($this->factura);
    }

    public function render()
    {
        
        
        
        return view('livewire.facturas.factura-show', [
            'debeSondear' => $this->factura->estado === Factura::ESTADO_ENVIADA,
        ]);
    }
}
