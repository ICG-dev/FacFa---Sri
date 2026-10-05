<x-slot:header>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
                {{ __('Facturas Electrónicas') }}
            </h1>
            <p class="text-sm text-muted-foreground mt-0.5">{{ __('Comprobantes de venta de tu empresa') }}</p>
        </div>

        @can('create', \App\Models\Factura::class)
            <a href="{{ route('facturas.create') }}" wire:navigate class="flex-shrink-0">
                <x-primary-button>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('Nueva factura') }}
                </x-primary-button>
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

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-lg px-3 md:px-4 py-3 flex items-center justify-between bg-card border border-border">
            <span class="text-[11px] font-semibold text-muted-foreground">{{ __('Total') }}</span>
            <span class="font-heading text-xl font-extrabold text-primary">{{ $stats['total'] }}</span>
        </div>
        <div class="rounded-lg px-3 md:px-4 py-3 flex items-center justify-between bg-card border border-border">
            <span class="text-[11px] font-semibold text-muted-foreground">{{ __('Autorizadas') }}</span>
            <span class="font-heading text-xl font-extrabold text-emerald-600">{{ $stats['autorizadas'] }}</span>
        </div>
        <div class="rounded-lg px-3 md:px-4 py-3 flex items-center justify-between bg-card border border-border">
            <span class="text-[11px] font-semibold text-muted-foreground">{{ __('Pendientes') }}</span>
            <span class="font-heading text-xl font-extrabold text-amber-600">{{ $stats['pendientes'] }}</span>
        </div>
        <div class="rounded-lg px-3 md:px-4 py-3 flex items-center justify-between bg-card border border-border">
            <span class="text-[11px] font-semibold text-muted-foreground">{{ __('Anuladas') }}</span>
            <span class="font-heading text-xl font-extrabold text-destructive">{{ $stats['anuladas'] }}</span>
        </div>
    </div>

    <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
        <div class="p-4 border-b border-border">
            <select wire:model.live="filtroEstado" class="rounded-md border-border bg-input-background text-foreground text-sm shadow-sm focus:border-ring focus:ring-ring">
                <option value="">{{ __('Todos los estados') }}</option>
                @foreach (['borrador', 'enviada', 'autorizada', 'rechazada', 'devuelta', 'pendiente_anular', 'anulada'] as $estado)
                    <option value="{{ $estado }}">{{ ucfirst(str_replace('_', ' ', $estado)) }}</option>
                @endforeach
            </select>
        </div>

        @if ($facturas->isEmpty())
            <div class="p-12 text-center text-muted-foreground">
                <p class="text-sm">{{ __('No hay facturas que coincidan con este filtro.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
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
                                    <div class="font-semibold text-foreground">{{ $factura->razon_social_comprador }}</div>
                                    <div class="text-muted-foreground font-mono text-[11px]">{{ $factura->identificacion_comprador }}</div>
                                </td>
                                <td class="px-4 md:px-5 py-3 text-muted-foreground whitespace-nowrap">{{ $factura->fecha_emision->format('d/m/Y') }}</td>
                                <td class="px-4 md:px-5 py-3 text-right font-mono font-semibold text-foreground whitespace-nowrap">${{ number_format($factura->importe_total, 2) }}</td>
                                <td class="px-4 md:px-5 py-3">
                                    <x-factura-estado-badge :estado="$factura->estado" />
                                </td>
                                <td class="px-4 md:px-5 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-icon-action :href="route('facturas.show', $factura)" wire:navigate :title="__('Ver')">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </x-icon-action>

                                        @can('update', $factura)
                                            <x-icon-action :href="route('facturas.edit', $factura)" wire:navigate :title="__('Editar')">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg>
                                            </x-icon-action>
                                        @endcan

                                        @can('confirmar', $factura)
                                            <x-icon-action
                                                color="emerald"
                                                :title="__('Confirmar y enviar')"
                                                wire:click="confirmar({{ $factura->id }})"
                                                wire:confirm="{{ __('¿Confirmar y enviar esta factura al SRI? Ya no podrá editarse.') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                            </x-icon-action>
                                        @endcan

                                        @can('anularBorrador', $factura)
                                            <x-icon-action
                                                color="destructive"
                                                :title="__('Descartar')"
                                                wire:click="anularBorrador({{ $factura->id }})"
                                                wire:confirm="{{ __('¿Descartar este borrador?') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </x-icon-action>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border">
                {{ $facturas->links() }}
            </div>
        @endif
    </div>
</div>
