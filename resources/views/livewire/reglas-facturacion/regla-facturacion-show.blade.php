<x-slot:header><h1 class="font-heading font-bold text-xl text-foreground">{{ $regla->nombre }}</h1></x-slot>
<div class="space-y-5 text-foreground" wire:poll.15s>
    @if(session('status'))<p role="status" class="p-3 rounded-lg bg-muted">{{ session('status') }}</p>@endif
    <section class="p-5 bg-card border border-border rounded-lg space-y-3">
        <p>{{ $regla->descripcion }}</p>
        <p class="text-sm">{{ $regla->activo ? 'Activa' : 'Pausada' }} · {{ ucfirst($regla->frecuencia) }} · {{ $regla->tipo_emision === 'automatica' ? 'Automática SRI' : 'Revisión' }}</p>
        <p class="text-sm">Vigencia: {{ $regla->fecha_inicio->format('d/m/Y') }} — {{ $regla->fecha_fin?->format('d/m/Y') ?? 'Sin fecha de fin' }}. Próxima ejecución: {{ $regla->proxima_ejecucion_at->format('d/m/Y H:i') }} (Ecuador).</p>
        <div class="flex flex-wrap gap-4 items-center">
            <a class="text-sm underline" wire:navigate href="{{ route('reglas-facturacion.index') }}">Volver a reglas</a>
            @can('update', $regla)<a class="text-sm underline" wire:navigate href="{{ route('reglas-facturacion.edit', $regla) }}">Editar regla</a>@endcan
            @can('ejecutar', $regla) @if($regla->activo)<x-primary-button wire:click="ejecutar" wire:loading.attr="disabled" wire:confirm="¿Ejecutar el ciclo actual para los clientes activos?">Ejecutar ahora</x-primary-button>@endif @endcan
        </div>
    </section>
    <section class="bg-card border border-border rounded-lg overflow-hidden">
        <h2 class="font-semibold p-4">Historial de ejecuciones</h2>
        <div class="overflow-x-auto"><table class="w-full text-sm text-left">
            <thead class="bg-muted"><tr><th class="p-3">Fecha</th><th class="p-3">Período</th><th class="p-3">Cliente</th><th class="p-3">Modo</th><th class="p-3">Estado / error</th><th class="p-3">Factura / acciones</th></tr></thead>
            <tbody class="divide-y divide-border">@forelse($ejecuciones as $ejecucion)
                <tr wire:key="ejecucion-{{ $ejecucion->id }}"><td class="p-3 whitespace-nowrap">{{ $ejecucion->ejecutado_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ $ejecucion->periodo }}</td><td class="p-3">{{ $ejecucion->cliente_identificacion }}</td><td class="p-3">{{ $ejecucion->modo_emision }}</td><td class="p-3 max-w-sm break-words">{{ $ejecucion->estado }}<p class="text-destructive">{{ $ejecucion->error_mensaje }}</p></td><td class="p-3">
                    @if($ejecucion->factura)
                        <a class="underline" wire:navigate href="{{ route('facturas.show', $ejecucion->factura) }}">Ver factura #{{ $ejecucion->factura_id }}</a>
                        @if($ejecucion->factura->estado === 'autorizada')<a class="underline ml-2" href="{{ route('facturas.ride', $ejecucion->factura) }}">RIDE</a><a class="underline ml-2" href="{{ route('facturas.xml', $ejecucion->factura) }}">XML</a>@endif
                    @elseif($ejecucion->estado === 'fallido')
                        @can('ejecutar', $regla)<button class="underline" wire:click="reintentar({{ $ejecucion->id }})" wire:loading.attr="disabled" wire:confirm="¿Reintentar únicamente este cliente y período?">Reintentar cliente</button>@endcan
                    @endif
                </td></tr>
            @empty<tr><td colspan="6" class="p-8 text-center text-muted-foreground">Todavía no hay ejecuciones.</td></tr>@endforelse</tbody>
        </table></div><div class="p-3">{{ $ejecuciones->links() }}</div>
    </section>
</div>
