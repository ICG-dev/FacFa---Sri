<x-slot:header>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
                {{ __('Empresas') }}
            </h1>
            <p class="text-sm text-muted-foreground mt-0.5">{{ __('Todas las empresas registradas en la plataforma') }}</p>
        </div>

        <a href="{{ route('empresas.create') }}" wire:navigate class="flex-shrink-0">
            <x-primary-button>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Nueva empresa') }}
            </x-primary-button>
        </a>
    </div>
</x-slot>

<div class="space-y-4">
    @if (session('status'))
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-4 py-3 text-sm text-blue-700 dark:text-blue-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-card text-card-foreground border border-border rounded-lg overflow-hidden">
        @if ($empresas->isEmpty())
            <div class="p-12 text-center text-muted-foreground">
                <p class="text-sm">{{ __('Todavía no hay empresas registradas.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/50">
                        <tr class="text-left text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                            <th class="px-4 md:px-5 py-3">{{ __('Empresa') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('RUC') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Ambiente') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Usuarios') }}</th>
                            <th class="px-4 md:px-5 py-3">{{ __('Certificado') }}</th>
                            <th class="px-4 md:px-5 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($empresas as $empresa)
                            <tr wire:key="empresa-{{ $empresa->id }}" class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 md:px-5 py-3">
                                    <div class="font-semibold text-foreground">{{ $empresa->razon_social }}</div>
                                    @if ($empresa->nombre_comercial)
                                        <div class="text-muted-foreground text-[11px]">{{ $empresa->nombre_comercial }}</div>
                                    @endif
                                </td>
                                <td class="px-4 md:px-5 py-3 font-mono text-muted-foreground">{{ $empresa->ruc }}</td>
                                <td class="px-4 md:px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold {{ $empresa->ambiente === 2 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800' }}">
                                        {{ $empresa->ambiente === 2 ? __('Producción') : __('Pruebas') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-5 py-3 text-right text-muted-foreground">{{ $empresa->users_count }}</td>
                                <td class="px-4 md:px-5 py-3">
                                    @if ($empresa->certificado_path)
                                        <span class="text-emerald-600 dark:text-emerald-400 text-xs font-semibold">{{ __('Cargado') }}</span>
                                    @else
                                        <span class="text-destructive text-xs font-semibold">{{ __('Falta') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 md:px-5 py-3 text-right whitespace-nowrap">
                                    <x-icon-action :href="route('empresas.edit', $empresa)" wire:navigate :title="__('Editar')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg>
                                    </x-icon-action>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border">
                {{ $empresas->links() }}
            </div>
        @endif
    </div>
</div>
