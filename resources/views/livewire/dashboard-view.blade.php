<x-slot:header>
    <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
        {{ __('Dashboard') }}
    </h1>
</x-slot>

@if ($esSuperAdmin)
    <div class="space-y-4 md:space-y-6">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Empresas registradas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-foreground mt-1">{{ $totalEmpresas }}</div>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Usuarios totales') }}</span>
                <div class="font-heading text-2xl font-extrabold text-foreground mt-1">{{ $totalUsuarios }}</div>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Sin certificado cargado') }}</span>
                <div class="font-heading text-2xl font-extrabold {{ $empresasSinCertificado > 0 ? 'text-destructive' : 'text-emerald-600' }} mt-1">{{ $empresasSinCertificado }}</div>
            </div>
        </div>

        <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
            <a href="{{ route('empresas.index') }}" wire:navigate class="text-sm text-accent font-semibold hover:underline">
                {{ __('Ir al listado de empresas →') }}
            </a>
        </div>
    </div>
@else
    <div class="space-y-4 md:space-y-6">
        <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Enviadas al SRI') }}</span>
                <div class="font-heading text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['enviadas'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Esperando respuesta de autorización') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Autorizadas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['autorizadas'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">${{ number_format($montoAutorizadoMes, 2) }} {{ __('este mes') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Pendientes') }}</span>
                <div class="font-heading text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['pendientes'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Borradores o devueltas por el SRI') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Anuladas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-destructive mt-1">{{ $stats['anuladas'] }}</div>
            </div>
        </div>

        <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-4 md:px-5 py-4 border-b border-border">
                <h3 class="font-heading text-sm font-bold text-foreground">{{ __('Últimas facturas enviadas al SRI') }}</h3>
                <a href="{{ route('facturas.index') }}" wire:navigate class="text-xs text-accent font-semibold hover:underline">{{ __('Ver todas →') }}</a>
            </div>

            @if ($ultimasEnviadas->isEmpty())
                <div class="p-10 text-center text-muted-foreground text-sm">
                    {{ __('Todavía no enviaste ninguna factura al SRI.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                                <th class="px-4 md:px-5 py-3">{{ __('Cliente') }}</th>
                                <th class="px-4 md:px-5 py-3">{{ __('Enviada') }}</th>
                                <th class="px-4 md:px-5 py-3 text-right">{{ __('Total') }}</th>
                                <th class="px-4 md:px-5 py-3">{{ __('Estado') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($ultimasEnviadas as $factura)
                                <tr wire:key="reciente-{{ $factura->id }}" class="hover:bg-muted/30 transition-colors">
                                    <td class="px-4 md:px-5 py-3">
                                        <a href="{{ route('facturas.show', $factura) }}" wire:navigate class="font-semibold text-foreground hover:text-accent transition-colors">
                                            {{ $factura->razon_social_comprador }}
                                        </a>
                                    </td>
                                    <td class="px-4 md:px-5 py-3 text-muted-foreground whitespace-nowrap">{{ $factura->enviado_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-4 md:px-5 py-3 text-right font-mono font-semibold text-foreground whitespace-nowrap">${{ number_format($factura->importe_total, 2) }}</td>
                                    <td class="px-4 md:px-5 py-3">
                                        <x-factura-estado-badge :estado="$factura->estado" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
