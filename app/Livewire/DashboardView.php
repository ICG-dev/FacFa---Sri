<?php

namespace App\Livewire;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
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
                'totalUsuarios' => User::query()->count(),
                'empresasSinCertificado' => Empresa::query()->whereNull('certificado_path')->count(),
            ]);
        }

        $facturasEmpresa = Factura::query()->where('empresa_id', $usuario->empresa_id);

        $stats = [
            'total' => (clone $facturasEmpresa)->count(),
            'autorizadas' => (clone $facturasEmpresa)->where('estado', Factura::ESTADO_AUTORIZADA)->count(),
            'pendientes' => (clone $facturasEmpresa)->whereIn('estado', [Factura::ESTADO_BORRADOR, Factura::ESTADO_ENVIADA])->count(),
            'rechazadas' => (clone $facturasEmpresa)->whereIn('estado', [Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA])->count(),
            'anuladas' => (clone $facturasEmpresa)->whereIn('estado', [Factura::ESTADO_ANULADA, Factura::ESTADO_PENDIENTE_ANULAR])->count(),
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

        $requierenAtencion = (clone $facturasEmpresa)
            ->whereIn('estado', [Factura::ESTADO_RECHAZADA, Factura::ESTADO_DEVUELTA])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $hoy = now()->startOfDay();
        $diasSemana = collect(range(6, 0))->map(function ($diasAtras) use ($facturasEmpresa, $hoy) {
            $fecha = $hoy->copy()->subDays($diasAtras);
            $total = (clone $facturasEmpresa)
                ->where('estado', Factura::ESTADO_AUTORIZADA)
                ->whereDate('fecha_emision', $fecha->toDateString())
                ->sum('importe_total');

            return [
                'fecha' => $fecha->toDateString(),
                'dia' => $fecha->locale('es')->translatedFormat('D j'),
                'total' => (float) $total,
            ];
        });
        $maxFacturacionDia = $diasSemana->max('total') ?: 1;

        return view('livewire.dashboard-view', [
            'esSuperAdmin' => false,
            'stats' => $stats,
            'montoAutorizadoMes' => $montoAutorizadoMes,
            'ultimasEnviadas' => $ultimasEnviadas,
            'requierenAtencion' => $requierenAtencion,
            'diasSemana' => $diasSemana,
            'maxFacturacionDia' => $maxFacturacionDia,
        ]);
    }
}
