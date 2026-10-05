<x-slot:header>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
                {{ __('Productos') }}
            </h1>
            <p class="text-sm text-muted-foreground mt-0.5">{{ __('Catálogo de tu empresa para agilizar la carga de facturas') }}</p>
        </div>

        @can('create', \App\Models\Producto::class)
            <a href="{{ route('productos.create') }}" wire:navigate class="flex-shrink-0">
                <x-primary-button>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('Nuevo producto') }}
                </x-primary-button>
            </a>
        @endcan
    </div>
</x-slot>

<div class="space-y-4">
    @if (session('status'))
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-4 py-3 text-sm text-blue-700 dark:text-blue-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
        <div class="p-4 border-b border-border">
            <x-text-input wire:model.live.debounce.300ms="buscar" type="text" placeholder="{{ __('Buscar por código o descripción...') }}" class="max-w-sm" />
        </div>

        @if ($productos->isEmpty())
            <div class="p-12 text-center text-muted-foreground">
                <p class="text-sm">{{ __('No hay productos que coincidan.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                            <th class="px-4 md:px-5 py-3">{{ __('Código') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Descripción') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Precio') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('IVA') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($productos as $producto)
                            <tr wire:key="producto-{{ $producto->id }}" class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 md:px-5 py-3 font-mono text-foreground">{{ $producto->codigo_principal }}</td>
                                <td class="px-4 md:px-5 py-3 text-foreground">{{ $producto->descripcion }}</td>
                                <td class="px-4 md:px-5 py-3 text-right font-mono text-foreground">${{ number_format($producto->precio_unitario, 2) }}</td>
                                <td class="px-4 md:px-5 py-3 text-right text-muted-foreground">{{ number_format($producto->tarifa, 0) }}%</td>
                                <td class="px-4 md:px-5 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('update', $producto)
                                            <x-icon-action :href="route('productos.edit', $producto)" wire:navigate :title="__('Editar')">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg>
                                            </x-icon-action>
                                        @endcan
                                        @can('delete', $producto)
                                            <x-icon-action
                                                color="destructive"
                                                :title="__('Eliminar')"
                                                wire:click="eliminar({{ $producto->id }})"
                                                wire:confirm="{{ __('¿Eliminar este producto del catálogo?') }}"
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
                {{ $productos->links() }}
            </div>
        @endif
    </div>
</div>
