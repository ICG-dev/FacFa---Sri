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
        <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-5">
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Total') }}</span>
                <div class="font-heading text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['total'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Facturas de tu empresa') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Autorizadas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['autorizadas'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">${{ number_format($montoAutorizadoMes, 2) }} {{ __('este mes') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Pendientes') }}</span>
                <div class="font-heading text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['pendientes'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Borradores y enviadas al SRI') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Rechazadas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-destructive mt-1">{{ $stats['rechazadas'] }}</div>
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Rechazadas o devueltas por el SRI') }}</p>
            </div>
            <div class="rounded-lg p-4 md:p-5 bg-card border border-border">
                <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">{{ __('Anuladas') }}</span>
                <div class="font-heading text-2xl font-extrabold text-destructive mt-1">{{ $stats['anuladas'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6 items-start">
            <section aria-labelledby="requiere-atencion" class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
                <div class="flex items-center gap-2 px-4 md:px-5 py-4 border-b border-border {{ $requierenAtencion->isNotEmpty() ? 'bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300' : '' }}">
                    @if ($requierenAtencion->isNotEmpty())
                        <svg class="h-5 w-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 2.1 18a2 2 0 0 0 1.7 3h16.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    @endif
                    <h2 id="requiere-atencion" class="font-heading text-sm font-bold">{{ __('Requiere atención') }}</h2>
                </div>

                @if ($requierenAtencion->isEmpty())
                    <p class="p-5 text-sm text-emerald-700 dark:text-emerald-400">{{ __('No hay facturas rechazadas ni devueltas pendientes de atención.') }}</p>
                @else
                    <ul class="divide-y divide-border">
                        @foreach ($requierenAtencion as $factura)
                            <li wire:key="atencion-{{ $factura->id }}" class="p-4 md:p-5 space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm break-words">{{ $factura->razon_social_comprador }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            <span class="font-mono">{{ $factura->secuencial ? $factura->establecimiento.'-'.$factura->punto_emision.'-'.str_pad($factura->secuencial, 9, '0', STR_PAD_LEFT) : __('Sin secuencial') }}</span>
                                            · {{ $factura->fecha_emision->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    <x-factura-estado-badge :estado="$factura->estado" />
                                </div>
                                <ul class="text-xs text-amber-800 dark:text-amber-300 space-y-1 break-words">
                                    @forelse ($factura->mensajes_error ?? [] as $mensaje)
                                        <li>
                                            @if (is_array($mensaje))
                                                @if (! empty($mensaje['identificador']))
                                                    <span class="font-mono">[{{ $mensaje['identificador'] }}]</span>
                                                @endif
                                                {{ $mensaje['mensaje'] ?? json_encode($mensaje) }}
                                                @if (! empty($mensaje['informacionAdicional']))
                                                    <span class="block mt-0.5">{{ $mensaje['informacionAdicional'] }}</span>
                                                @endif
                                            @else
                                                {{ $mensaje }}
                                            @endif
                                        </li>
                                    @empty
                                        <li>{{ __('El SRI no proporcionó un motivo.') }}</li>
                                    @endforelse
                                </ul>
                                <a href="{{ route('facturas.show', $factura) }}" wire:navigate class="inline-block text-xs font-semibold text-accent hover:underline">
                                    @can('reabrirBorrador', $factura)
                                        {{ __('Ver y corregir factura →') }}
                                    @else
                                        {{ __('Ver factura →') }}
                                    @endcan
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <figure aria-labelledby="facturacion-semanal" class="bg-card text-card-foreground border border-border rounded-lg p-4 md:p-5">
                <figcaption id="facturacion-semanal" class="font-heading text-sm font-bold text-foreground">{{ __('Facturación de los últimos 7 días') }}</figcaption>
                <p class="text-xs text-muted-foreground mt-1">{{ __('Importes de facturas autorizadas') }}</p>
                <ol class="grid grid-cols-7 gap-2 mt-6">
                    @foreach ($diasSemana as $dia)
                        <li wire:key="facturacion-{{ $dia['fecha'] }}" class="min-w-0 text-center" title="{{ $dia['fecha'] }}: ${{ number_format($dia['total'], 2) }}">
                            <div class="h-40 flex items-end border-b border-border" aria-hidden="true">
                                <div class="w-full bg-primary rounded-t-sm" style="height: {{ ($dia['total'] / $maxFacturacionDia) * 100 }}%"></div>
                            </div>
                            <span class="block text-[10px] sm:text-xs text-muted-foreground mt-2 truncate">{{ $dia['dia'] }}</span>
                            <span class="sr-only">{{ $dia['fecha'] }}:</span>
                            <span class="block text-[10px] sm:text-xs font-mono mt-1 truncate">${{ number_format($dia['total'], 2) }}</span>
                        </li>
                    @endforeach
                </ol>
            </figure>
        </div>

        <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-4 md:px-5 py-4 border-b border-border">
                <h3 class="font-heading text-sm font-bold text-foreground">{{ __('Últimas facturas enviadas al SRI') }}</h3>
                <a href="{{ route('facturas.index') }}" wire:navigate class="text-xs text-accent font-semibold hover:underline">{{ __('Ver todas →') }}</a>
            </div>

            @if ($ultimasEnviadas->isEmpty())
                <div class="p-10 text-center text-muted-foreground text-sm">
                    {{ __('Aún no has emitido facturas al SRI.') }}
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
