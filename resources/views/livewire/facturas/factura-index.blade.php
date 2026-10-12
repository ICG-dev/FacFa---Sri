<x-slot:header>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
                {{ __('Facturas Electrónicas') }}
            </h1>
            <p class="text-sm text-muted-foreground mt-0.5">{{ __('Comprobantes de venta de tu empresa') }}</p>
        </div>

        @can('create', \App\Models\Factura::class)
            <a href="{{ route('facturas.create') }}" wire:navigate class="w-full sm:w-auto flex-shrink-0 inline-flex items-center justify-center gap-2 h-9 px-4 py-2 bg-accent text-accent-foreground rounded-md text-sm font-semibold shadow-sm hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Nueva factura') }}
            </a>
        @endcan
    </div>
</x-slot>

<div class="space-y-4 md:space-y-5">
    @if (session('status'))
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-4 py-3 text-sm text-blue-700 dark:text-blue-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-4">
        @foreach ([
            'total' => [__('Total'), '', 'text-primary'],
            'autorizadas' => [__('Autorizadas'), 'autorizada', 'text-emerald-600'],
            'pendientes' => [__('Pendientes'), 'pendientes', 'text-amber-600'],
            'rechazadas' => [__('Rechazadas'), 'rechazadas', 'text-destructive'],
            'anuladas' => [__('Anuladas'), 'anuladas', 'text-muted-foreground'],
        ] as $metrica => [$etiqueta, $filtro, $color])
            <button type="button" wire:click="$set('filtroEstado', '{{ $filtroEstado === $filtro ? '' : $filtro }}')"
                aria-pressed="{{ $filtroEstado === $filtro ? 'true' : 'false' }}"
                class="min-w-0 rounded-lg px-3 md:px-4 py-3 flex flex-wrap items-center justify-between gap-2 bg-card border cursor-pointer hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring transition-colors {{ $filtroEstado === $filtro ? 'border-primary' : 'border-border' }}">
                <span class="text-[11px] font-semibold text-muted-foreground">{{ $etiqueta }}</span>
                <span class="max-w-full break-all font-heading text-xl font-extrabold {{ $color }}">{{ $stats[$metrica] }}</span>
            </button>
        @endforeach
    </div>

    <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
        <div class="p-4 border-b border-border flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-auto sm:flex-1 sm:min-w-64">
                <label for="factura-busqueda" class="sr-only">{{ __('Buscar facturas') }}</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path stroke-linecap="round" d="m16 16 4 4" /></svg>
                    <input id="factura-busqueda" type="search" wire:model.live.debounce.350ms="busqueda"
                        placeholder="{{ __('Buscar por cliente, identificación, clave o secuencial') }}"
                        class="w-full pl-9 pr-9 rounded-md border-border bg-input-background text-foreground text-sm shadow-sm focus:border-ring focus:ring-ring" />
                    @if ($busqueda !== '')
                        <button type="button" wire:click="$set('busqueda', '')" aria-label="{{ __('Limpiar búsqueda') }}" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M6 18 18 6" /></svg>
                        </button>
                    @endif
                </div>
            </div>
            <select aria-label="{{ __('Estado de la factura') }}" wire:model.live="filtroEstado" class="rounded-md border-border bg-input-background text-foreground text-sm shadow-sm focus:border-ring focus:ring-ring">
                <option value="">{{ __('Todos los estados') }}</option>
                <option value="pendientes">{{ __('Pendientes (borradores y enviadas)') }}</option>
                <option value="rechazadas">{{ __('Rechazadas / devueltas') }}</option>
                <option value="anuladas">{{ __('Anuladas / pendientes de anulación') }}</option>
                @foreach (['borrador', 'enviada', 'autorizada', 'rechazada', 'devuelta', 'pendiente_anular', 'anulada'] as $estado)
                    <option value="{{ $estado }}">{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                @endforeach
            </select>
            <div>
                <label for="factura-fecha-desde" class="block mb-1 text-xs text-muted-foreground">{{ __('Desde') }}</label>
                <input id="factura-fecha-desde" type="date" wire:model.live="fechaDesde" class="rounded-md border-border bg-input-background text-foreground text-sm shadow-sm focus:border-ring focus:ring-ring" />
            </div>
            <div>
                <label for="factura-fecha-hasta" class="block mb-1 text-xs text-muted-foreground">{{ __('Hasta') }}</label>
                <input id="factura-fecha-hasta" type="date" wire:model.live="fechaHasta" class="rounded-md border-border bg-input-background text-foreground text-sm shadow-sm focus:border-ring focus:ring-ring" />
            </div>
            @if ($this->filtrosActivos)
                <button type="button" wire:click="limpiarFiltros" class="px-3 py-2 rounded-md text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring transition-colors">
                    {{ __('Limpiar filtros') }}
                </button>
            @endif
        </div>

        @if ($facturas->isEmpty())
            <div class="p-8 sm:p-12 text-center space-y-3">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-secondary text-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                @if ($this->filtrosActivos)
                    <p class="text-sm font-semibold text-foreground">{{ __('No se encontraron facturas con los filtros aplicados.') }}</p>
                    <button type="button" wire:click="limpiarFiltros" class="text-xs text-primary hover:underline font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-sm">
                        {{ __('Limpiar filtros') }}
                    </button>
                @elseif ($stats['total'] > 0)
                    <p class="text-sm font-semibold text-foreground">{{ __('No hay facturas en esta página.') }}</p>
                    <a href="{{ route('facturas.index') }}" wire:navigate class="inline-flex text-xs text-primary hover:underline font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-sm">
                        {{ __('Volver a la primera página') }}
                    </a>
                @else
                    <p class="text-sm font-semibold text-foreground">{{ __('Aún no tienes facturas registradas.') }}</p>
                    <p class="text-xs text-muted-foreground">{{ __('Comienza emitiendo tu primera factura electrónica.') }}</p>
                    @can('create', \App\Models\Factura::class)
                        <div class="pt-2">
                            <a href="{{ route('facturas.create') }}" wire:navigate class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-accent text-accent-foreground text-xs font-semibold shadow-sm hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background transition-colors">
                                {{ __('Emitir primera factura') }}
                            </a>
                        </div>
                    @endcan
                @endif
            </div>
        @else
            <div class="overflow-x-auto min-h-[260px]">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                            <th class="px-4 md:px-5 py-3">{{ __('Comprobante') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Cliente') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Fecha') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Total') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Estado') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($facturas as $factura)
                            <tr wire:key="factura-{{ $factura->id }}" class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 md:px-5 py-3">
                                    @if ($factura->secuencial)
                                        <a href="{{ route('facturas.show', $factura) }}" wire:navigate class="font-mono font-semibold text-foreground hover:text-primary whitespace-nowrap">
                                            {{ sprintf('%03d-%03d-%09d', $factura->establecimiento, $factura->punto_emision, $factura->secuencial) }}
                                        </a>
                                    @else
                                        <span class="font-mono italic text-muted-foreground">{{ __('Borrador') }}</span>
                                    @endif
                                    @if ($factura->clave_acceso)
                                        <div x-data="{ copiado: false, errorCopia: false }" class="mt-1 text-[11px] text-muted-foreground">
                                            <div class="flex items-center gap-1">
                                                <span title="{{ $factura->clave_acceso }}" class="font-mono">{{ \Illuminate\Support\Str::limit($factura->clave_acceso, 12) }}</span>
                                                <x-icon-action :title="__('Copiar clave de acceso')" color="muted" class="h-6 w-6"
                                                    x-on:click="copiado = false; errorCopia = false; try { await navigator.clipboard.writeText(@js($factura->clave_acceso)); copiado = true; } catch { errorCopia = true; }">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9h11v11H9zM5 15H3V3h12v2" /></svg>
                                                </x-icon-action>
                                            </div>
                                            <span x-show="copiado" style="display: none" role="status" class="text-emerald-600">{{ __('Clave copiada') }}</span>
                                            <p x-show="errorCopia" style="display: none" role="alert" class="max-w-xs text-destructive">
                                                {{ __('No se pudo copiar. Selecciona la clave para copiarla manualmente:') }}
                                                <span class="break-all font-mono select-all">{{ $factura->clave_acceso }}</span>
                                            </p>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 md:px-5 py-3">
                                    <div class="font-semibold text-foreground max-w-xs truncate" title="{{ $factura->razon_social_comprador }}">{{ $factura->razon_social_comprador }}</div>
                                    <div class="text-muted-foreground font-mono text-[11px]">{{ $factura->identificacion_comprador }}</div>
                                </td>
                                <td class="px-4 md:px-5 py-3 text-muted-foreground whitespace-nowrap">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 md:px-5 py-3 text-right font-mono font-semibold text-foreground whitespace-nowrap">${{ number_format($factura->importe_total, 2) }}</td>
                                <td class="px-4 md:px-5 py-3">
                                    <div class="flex items-center gap-1">
                                        <x-factura-estado-badge :estado="$factura->estado" />
                                        @if (in_array($factura->estado, [\App\Models\Factura::ESTADO_RECHAZADA, \App\Models\Factura::ESTADO_DEVUELTA], true) && $factura->mensajes_error)
                                            @php($mensajeSri = $factura->mensajes_error[0] ?? $factura->mensajes_error)
                                            <x-dropdown width="w-80 max-w-[calc(100vw-1rem)]" floating>
                                                <x-slot:trigger>
                                                    <x-icon-action :title="__('Ver motivo del SRI')" color="destructive" aria-describedby="sri-error-{{ $factura->id }}" x-bind:aria-expanded="open.toString()">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 1.7 18.6A1.6 1.6 0 0 0 3.1 21h17.8a1.6 1.6 0 0 0 1.4-2.4L13.7 3.8a2 2 0 0 0-3.4 0Z" /></svg>
                                                    </x-icon-action>
                                                </x-slot:trigger>
                                                <x-slot:content>
                                                    <div id="sri-error-{{ $factura->id }}" role="tooltip" class="space-y-2 p-3 text-left text-xs break-words">
                                                        <p class="font-semibold">{{ data_get($mensajeSri, 'mensaje') ?: json_encode($mensajeSri, JSON_UNESCAPED_UNICODE) }}</p>
                                                        @if (data_get($mensajeSri, 'informacionAdicional'))
                                                            <p class="text-muted-foreground">{{ data_get($mensajeSri, 'informacionAdicional') }}</p>
                                                        @endif
                                                    </div>
                                                </x-slot:content>
                                            </x-dropdown>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 md:px-5 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end">
                                        <x-dropdown width="w-64" floating>
                                            <x-slot:trigger>
                                                <x-icon-action :title="__('Menú de acciones')" color="muted" aria-haspopup="true" x-bind:aria-expanded="open.toString()">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="12" cy="19" r="2" /></svg>
                                                </x-icon-action>
                                            </x-slot:trigger>
                                            <x-slot:content>
                                                <a href="{{ route('facturas.show', $factura) }}" wire:navigate title="{{ __('Ver detalle') }}" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                    <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <span>{{ __('Ver detalle') }}</span>
                                                </a>

                                                @can('descargarRide', $factura)
                                                    <a href="{{ route('facturas.ride', $factura) }}" title="{{ __('Descargar RIDE') }}" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                        <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4" /></svg>
                                                        <span>{{ __('Descargar RIDE') }}</span>
                                                    </a>
                                                @endcan

                                                @can('descargarXml', $factura)
                                                    @if ($factura->xml_autorizado || $factura->xml_firmado || $factura->xml_generado)
                                                        <button wire:click="verXmlModal({{ $factura->id }})" type="button" title="{{ __('Ver XML') }}" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                            <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m8 8-4 4 4 4m8-8 4 4-4 4m-3-10-2 20" /></svg>
                                                            <span>{{ __('Ver XML') }}</span>
                                                        </button>
                                                        <a href="{{ route('facturas.xml', ['factura' => $factura, 'download' => 1]) }}" title="{{ __('Descargar XML') }}" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                            <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6v20h12V6l-4-4ZM14 2v4h4m-6 4v8m-3-3 3 3 3-3" /></svg>
                                                            <span>{{ __('Descargar XML') }}</span>
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('reabrirBorrador', $factura)
                                                    <button wire:click="reabrirBorrador({{ $factura->id }})" wire:confirm="{{ __('¿Deseas reabrir esta factura para editarla?') }}" type="button" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                        <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10a9 9 0 1 1 2.5 8M3 4v6h6" /></svg>
                                                        <span>{{ __('Reabrir y corregir') }}</span>
                                                    </button>
                                                @endcan

                                                @can('update', $factura)
                                                    @if ($factura->estado === \App\Models\Factura::ESTADO_BORRADOR)
                                                    <a href="{{ route('facturas.edit', $factura) }}" wire:navigate title="{{ __('Editar borrador') }}" class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors">
                                                        <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg>
                                                        <span>{{ __('Editar borrador') }}</span>
                                                    </a>
                                                    @endif
                                                @endcan

                                                @can('confirmar', $factura)
                                                    <button
                                                        wire:click="confirmar({{ $factura->id }})"
                                                        wire:confirm="{{ __('¿Confirmar y enviar esta factura al SRI? Ya no podrá editarse.') }}"
                                                        type="button"
                                                        title="{{ __('Confirmar y enviar al SRI') }}"
                                                        class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-foreground hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none transition-colors"
                                                    >
                                                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                                        <span>{{ __('Confirmar y enviar al SRI') }}</span>
                                                    </button>
                                                @endcan

                                                @can('anularBorrador', $factura)
                                                    <button
                                                        wire:click="anularBorrador({{ $factura->id }})"
                                                        wire:confirm="{{ __('¿Descartar este borrador?') }}"
                                                        type="button"
                                                        title="{{ __('Descartar borrador') }}"
                                                        class="group flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-destructive hover:bg-destructive/10 focus-visible:bg-destructive/10 focus-visible:outline-none transition-colors"
                                                    >
                                                        <svg class="h-4 w-4 text-destructive/80 group-hover:text-destructive group-hover:scale-110 transition-transform duration-150 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                        <span>{{ __('Descartar borrador') }}</span>
                                                    </button>
                                                @endcan
                                            </x-slot:content>
                                        </x-dropdown>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
        <div class="p-4 border-t border-border flex flex-col gap-3">
            <span class="text-xs text-muted-foreground">{{ __('Mostrando :from a :to de :total facturas', ['from' => $facturas->firstItem() ?? 0, 'to' => $facturas->lastItem() ?? 0, 'total' => $facturas->total()]) }}</span>
            {{ $facturas->links() }}
        </div>
    </div>
    @if ($facturaXmlId !== null)
        <x-factura-xml-modal :factura="$this->facturaXml" :xml="$xmlModalContenido" :numero="$xmlModalNumero" />
    @endif
</div>
