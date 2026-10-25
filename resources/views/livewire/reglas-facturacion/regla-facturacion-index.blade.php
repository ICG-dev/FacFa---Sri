<x-slot:header>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-heading font-bold text-xl text-foreground">Reglas de facturación</h1>
        @can('create', \App\Models\ReglaFacturacion::class)
            <a href="{{ route('reglas-facturacion.create') }}" wire:navigate><x-primary-button>Nueva regla</x-primary-button></a>
        @endcan
    </div>
</x-slot>
<div class="space-y-4">
    @if(session('status')) <p role="status" class="p-3 rounded-lg bg-muted text-foreground">{{ session('status') }}</p> @endif
    <x-input-error :messages="$errors->all()" />
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <label class="text-sm text-foreground">Nombre<x-text-input class="w-full" wire:model.live.debounce.300ms="buscar" /></label>
        <label class="text-sm text-foreground">Estado<select class="w-full rounded-md border-border bg-input-background text-foreground" wire:model.live="estado"><option value="">Todos</option><option value="activa">Activas</option><option value="pausada">Pausadas</option></select></label>
        <label class="text-sm text-foreground">Emisión<select class="w-full rounded-md border-border bg-input-background text-foreground" wire:model.live="modo"><option value="">Todas</option><option value="revision">Revisión</option><option value="automatica">Automática SRI</option></select></label>
        <label class="text-sm text-foreground">Frecuencia<select class="w-full rounded-md border-border bg-input-background text-foreground" wire:model.live="frecuencia"><option value="">Todas</option>@foreach(['unica' => 'Única', 'diaria' => 'Diaria', 'semanal' => 'Semanal', 'mensual' => 'Mensual', 'anual' => 'Anual'] as $valor => $texto)<option value="{{ $valor }}">{{ $texto }}</option>@endforeach</select></label>
    </div>
    <div class="overflow-x-auto rounded-lg border border-border bg-card text-card-foreground">
        <table class="w-full text-sm text-left">
            <thead class="bg-muted"><tr><th class="p-3">Regla</th><th class="p-3">Emisión</th><th class="p-3">Frecuencia</th><th class="p-3">Próxima ejecución</th><th class="p-3">Estado</th><th class="p-3 text-right">Acciones</th></tr></thead>
            <tbody class="divide-y divide-border">
            @forelse($reglas as $regla)
                <tr wire:key="regla-{{ $regla->id }}" class="hover:bg-muted/30 transition-colors">
                    <td class="p-3"><a class="text-primary underline font-medium" wire:navigate href="{{ route('reglas-facturacion.show', $regla) }}">{{ $regla->nombre }}</a></td>
                    <td class="p-3"><span @class(['rounded-full px-2 py-1 text-xs whitespace-nowrap', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' => $regla->tipo_emision === 'automatica', 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' => $regla->tipo_emision === 'revision'])>{{ $regla->tipo_emision === 'automatica' ? 'Automática SRI' : 'Revisión' }}</span></td>
                    <td class="p-3">{{ ucfirst($regla->frecuencia) }}</td><td class="p-3 whitespace-nowrap">{{ $regla->proxima_ejecucion_at->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $regla->activo ? 'Activa' : 'Pausada' }}</td>
                    <td class="p-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            <x-icon-action :href="route('reglas-facturacion.show', $regla)" wire:navigate color="muted" :title="__('Ver detalle')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </x-icon-action>

                            @can('update', $regla)
                                <x-icon-action :href="route('reglas-facturacion.edit', $regla)" wire:navigate color="primary" :title="__('Editar')">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                    </svg>
                                </x-icon-action>

                                @if($regla->activo)
                                    <x-icon-action wire:click="toggleActivo({{ $regla->id }})" color="muted" :title="__('Pausar')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                        </svg>
                                    </x-icon-action>
                                @else
                                    <x-icon-action wire:click="toggleActivo({{ $regla->id }})" color="emerald" :title="__('Activar')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                        </svg>
                                    </x-icon-action>
                                @endif
                            @endcan

                            @can('ejecutar', $regla)
                                @if($regla->activo)
                                    <x-icon-action wire:click="ejecutar({{ $regla->id }})" wire:loading.attr="disabled" wire:confirm="{{ __('¿Ejecutar el ciclo actual para los clientes activos?') }}" color="emerald" :title="__('Ejecutar ahora')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                        </svg>
                                    </x-icon-action>
                                @endif
                            @endcan

                            @can('delete', $regla)
                                <x-icon-action wire:click="eliminar({{ $regla->id }})" wire:confirm="{{ __('¿Eliminar esta regla?') }}" color="destructive" :title="__('Eliminar')">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </x-icon-action>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty <tr><td colspan="6" class="p-8 text-center text-muted-foreground">No hay reglas que coincidan.</td></tr> @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $reglas->links() }}</div>
    </div>
</div>
