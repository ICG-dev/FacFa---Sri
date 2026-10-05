<?php

namespace App\Livewire;

use App\Models\Empresa;
use App\Models\Factura;
use Livewire\Component;

class DashboardView extends Component
{
    public function render()
    {
        $usuario = auth()->user();

        if ($usuario->esSuperAdmin()) {
            return view('livewire.dashboard-view', [
                'esSuperAdmin' => true,
                'totalEmpresas' => Empresa::query()->count(),
                'totalUsuarios' => \App\Models\User::query()->count(),
                'empresasSinCertificado' => Empresa::query()->whereNull('certificado_path')->count(),
            ]);
        }

        $facturasEmpresa = Factura::query()->where('empresa_id', $usuario->empresa_id);

        $stats = [
            'enviadas' => (clone $facturasEmpresa)->where('estado', Factura::ESTADO_ENVIADA)->count(),
            'autorizadas' => (clone $facturasEmpresa)->where('estado', Factura::ESTADO_AUTORIZADA)->count(),
            'pendientes' => (clone $facturasEmpresa)->whereIn('estado', [Factura::ESTADO_BORRADOR, Factura::ESTADO_DEVUELTA])->count(),
            'anuladas' => (clone $facturasEmpresa)->whereIn('estado', [Factura::ESTADO_ANULADA, Factura::ESTADO_RECHAZADA])->count(),
        ];

        $montoAutorizadoMes = (clone $facturasEmpresa)
            ->where('estado', Factura::ESTADO_AUTORIZADA)
            ->whereMonth('fecha_emision', now()->month)
            ->whereYear('fecha_emision', now()->year)
            ->sum('importe_total');

        $ultimasEnviadas = (clone $facturasEmpresa)
            ->whereIn('estado', [Factura::ESTADO_ENVIADA, Factura::ESTADO_AUTORIZADA, Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA])
            ->latest('enviado_at')
            ->take(5)
            ->get();

        return view('livewire.dashboard-view', [
            'esSuperAdmin' => false,
            'stats' => $stats,
            'montoAutorizadoMes' => $montoAutorizadoMes,
            'ultimasEnviadas' => $ultimasEnviadas,
        ]);
    }
}
