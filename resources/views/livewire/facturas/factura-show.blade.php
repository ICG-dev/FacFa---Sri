<x-slot:header>
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
            {{ __('Factura') }} — {{ $factura->razon_social_comprador }}
        </h1>
        <a href="{{ route('facturas.index') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">
            {{ __('Volver al listado') }}
        </a>
    </div>
</x-slot>

<div class="max-w-4xl space-y-6" @if($debeSondear) wire:poll.5s="refrescar" @endif>

            <div class="bg-card text-card-foreground border border-border rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <x-factura-estado-badge :estado="$factura->estado" class="text-sm px-3 py-1" />

                    @can('descargarRide', $factura)
                        <a href="{{ route('facturas.ride', $factura) }}" class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                            {{ __('Descargar RIDE') }}
                        </a>
                    @endcan

                    @if ($debeSondear)
                        <span class="text-xs text-muted-foreground flex items-center gap-1">
                            <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('Esperando respuesta del SRI…') }}
                        </span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-muted-foreground">{{ __('Cliente') }}</dt>
                        <dd class="text-foreground">{{ $factura->razon_social_comprador }} <span class="font-mono text-xs text-muted-foreground">({{ $factura->identificacion_comprador }})</span></dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ __('Fecha de emisión') }}</dt>
                        <dd class="text-foreground">{{ $factura->fecha_emision->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ __('Clave de acceso') }}</dt>
                        <dd class="text-foreground font-mono text-xs break-all">{{ $factura->clave_acceso ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ __('Número de autorización') }}</dt>
                        <dd class="text-foreground font-mono text-xs break-all">{{ $factura->numero_autorizacion ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ __('Total') }}</dt>
                        <dd class="text-foreground font-semibold">${{ number_format($factura->importe_total, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ __('Creada por') }}</dt>
                        <dd class="text-foreground">{{ $factura->usuario->name }}</dd>
                    </div>
                </dl>

                @if (! empty($factura->mensajes_error))
                    <div class="rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-2">{{ __('Mensajes del SRI') }}</p>
                        <ul class="text-sm text-amber-700 dark:text-amber-400 space-y-1 list-disc list-inside">
                            @foreach ($factura->mensajes_error as $mensaje)
                                <li>{{ $mensaje['mensaje'] ?? json_encode($mensaje) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @can('solicitarAnulacion', $factura)
                    <div class="pt-4 border-t border-border">
                        <x-danger-button type="button" wire:click="solicitarAnulacion" wire:confirm="{{ __('Esto solo registra en el sistema que iniciaste el trámite de anulación en el portal SRI en línea. ¿Continuar?') }}">
                            {{ __('Marcar solicitud de anulación') }}
                        </x-danger-button>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ __('El trámite real se realiza en el portal SRI en línea. Esta acción solo sincroniza el estado en el sistema.') }}
                        </p>
                    </div>
                @elseif ($this->viaAnulacion === 'nota_credito')
                    <div class="pt-4 border-t border-border text-sm text-muted-foreground">
                        {{ __('El plazo de anulación en línea (día 7 del mes siguiente a la emisión) ya venció. Para anular esta factura se requiere emitir una Nota de Crédito (módulo no disponible todavía).') }}
                    </div>
                @elseif ($this->viaAnulacion === 'no_disponible' && $factura->esConsumidorFinal())
                    <div class="pt-4 border-t border-border text-sm text-muted-foreground">
                        {{ __('Esta factura fue emitida a consumidor final: no puede anularse por ninguna vía una vez transmitida al SRI.') }}
                    </div>
                @elseif ($this->viaAnulacion === 'no_disponible')
                    <div class="pt-4 border-t border-border text-sm text-muted-foreground">
                        {{ __('Esta factura ya no puede anularse: superó los 12 meses desde la emisión.') }}
                    </div>
                @endif
            </div>

            <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            <th class="px-6 py-3">{{ __('Descripción') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Cant.') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Precio unit.') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('IVA') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($factura->detalles as $detalle)
                            <tr>
                                <td class="px-6 py-3 text-foreground">{{ $detalle->descripcion }}</td>
                                <td class="px-6 py-3 text-right text-muted-foreground">{{ number_format($detalle->cantidad, 2) }}</td>
                                <td class="px-6 py-3 text-right text-muted-foreground">${{ number_format($detalle->precio_unitario, 2) }}</td>
                                <td class="px-6 py-3 text-right text-muted-foreground">{{ number_format($detalle->tarifa, 0) }}%</td>
                                <td class="px-6 py-3 text-right text-foreground">${{ number_format($detalle->precio_total_sin_impuesto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
</div>
