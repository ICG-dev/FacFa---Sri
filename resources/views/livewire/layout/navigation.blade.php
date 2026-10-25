<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function migasDePan(): array
    {
        $ruta = request()->route()?->getName();
        $paginas = [
            'reglas-facturacion.index' => [__('Reglas de facturación'), null],
            'reglas-facturacion.create' => [__('Nueva regla'), 'reglas-facturacion.index'],
            'reglas-facturacion.edit' => [__('Editar regla'), 'reglas-facturacion.index'],
            'reglas-facturacion.show' => [__('Historial'), 'reglas-facturacion.index'],
            'dashboard' => [__('Dashboard'), null],
            'facturas.index' => [__('Facturas'), null],
            'facturas.create' => [__('Nueva factura'), 'facturas.index'],
            'facturas.edit' => [__('Editar factura'), 'facturas.index'],
            'facturas.show' => [__('Detalle'), 'facturas.index'],
            'productos.index' => [__('Productos'), null],
            'productos.create' => [__('Nuevo producto'), 'productos.index'],
            'productos.edit' => [__('Editar producto'), 'productos.index'],
            'usuarios.index' => [__('Usuarios'), null],
            'usuarios.create' => [__('Nuevo usuario'), 'usuarios.index'],
            'usuarios.edit' => [__('Editar usuario'), 'usuarios.index'],
            'empresas.index' => [__('Mi empresa'), null],
            'empresas.create' => [__('Mi empresa'), null],
            'empresas.edit' => [__('Mi empresa'), null],
            'profile' => [__('Perfil'), null],
        ];
        [$titulo, $padre] = $paginas[$ruta] ?? [config('app.name'), null];
        $migas = [['titulo' => __('Sistema'), 'url' => route('dashboard')]];
        if ($padre) {
            $migas[] = ['titulo' => $paginas[$padre][0], 'url' => route($padre)];
        }
        $migas[] = ['titulo' => $titulo, 'url' => null];

        return $migas;
    }
}; ?>

<header class="flex items-center gap-2 md:gap-4 px-3 md:px-6 py-3 flex-shrink-0 z-10 bg-card border-b border-border">
    <button @click="mobileOpen = true" class="p-1.5 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors lg:hidden">
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>
    <button @click="sidebarOpen = ! sidebarOpen" class="p-1.5 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors hidden lg:block">
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>

    @php($migas = $this->migasDePan())
    <nav aria-label="{{ __('Migas de pan') }}" class="hidden sm:block min-w-0 text-sm text-muted-foreground">
        <ol class="flex items-center gap-1.5 min-w-0">
            @foreach ($migas as $miga)
                <li class="flex items-center gap-1.5 min-w-0 {{ $loop->first ? 'hidden md:flex' : '' }}">
                    @unless ($loop->first)
                        <svg class="h-3.5 w-3.5 shrink-0 {{ $loop->iteration === 2 ? 'hidden md:block' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    @endunless
                    @if ($miga['url'])
                        <a href="{{ $miga['url'] }}" wire:navigate class="truncate hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-sm">{{ $miga['titulo'] }}</a>
                    @else
                        <span aria-current="page" class="truncate font-semibold text-foreground">{{ $miga['titulo'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    <span class="sm:hidden font-heading font-semibold text-sm text-foreground truncate">{{ $migas[array_key_last($migas)]['titulo'] }}</span>

    <div class="flex-1"></div>

    @php($ambiente = auth()->user()->empresa?->ambiente)
    @if (in_array($ambiente, [1, 2], true))
        <span @class([
            'inline-flex items-center gap-1.5 shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold tracking-wide',
            'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30' => $ambiente === 1,
            'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' => $ambiente === 2,
        ]) title="{{ $ambiente === 1 ? __('Ambiente de pruebas SRI — Comprobantes sin validez tributaria') : __('Ambiente de producción SRI') }}">
            <span @class(['h-1.5 w-1.5 rounded-full', 'bg-amber-500 animate-pulse motion-reduce:animate-none' => $ambiente === 1, 'bg-emerald-500' => $ambiente === 2]) aria-hidden="true"></span>
            <span class="sr-only">{{ __('Ambiente SRI:') }}</span>
            {{ $ambiente === 1 ? __('PRUEBAS') : __('PRODUCCIÓN') }}
        </span>
    @endif

    <button
        onclick="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';"
        class="p-2 rounded-lg text-muted-foreground bg-secondary hover:opacity-80 transition-opacity"
        title="{{ __('Cambiar tema') }}"
        aria-label="{{ __('Cambiar tema') }}"
    >
        <svg class="h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
        <svg class="h-4 w-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
    </button>

    <x-dropdown align="right" width="w-80 max-w-[calc(100vw-1rem)]" floating>
        <x-slot name="trigger">
            <button type="button" class="relative p-2 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                title="{{ __('Notificaciones') }}" aria-label="{{ __('Notificaciones') }}"
                aria-controls="notificaciones-panel" x-bind:aria-expanded="open.toString()">
                <svg class="h-[17px] w-[17px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
            </button>
        </x-slot>
        <x-slot name="content">
            <section id="notificaciones-panel" role="region" aria-labelledby="notificaciones-titulo">
                <h2 id="notificaciones-titulo" class="px-4 py-3 border-b border-border text-sm font-semibold">{{ __('Notificaciones') }}</h2>
                <div class="p-4 space-y-2">
                    <p class="text-sm font-semibold">{{ __('No tienes notificaciones pendientes') }}</p>
                    <p class="text-xs text-muted-foreground">{{ __('Te avisaremos aquí si el SRI rechaza o devuelve algún comprobante.') }}</p>
                </div>
            </section>
        </x-slot>
    </x-dropdown>

    <div class="flex items-center ms-2">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg transition-colors hover:bg-accent">
                    <div class="rounded-full flex items-center justify-center flex-shrink-0 w-8 h-8 bg-primary text-primary-foreground font-heading text-xs font-bold">
                        {{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                    </div>
                    <div class="text-left hidden lg:block">
                        <div class="text-xs font-semibold text-foreground leading-tight">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-muted-foreground leading-tight">{{ auth()->user()->getRoleNames()->first() ? ucfirst(auth()->user()->getRoleNames()->first()) : '' }}</div>
                    </div>
                    <svg class="h-3.5 w-3.5 text-muted-foreground hidden lg:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile')" wire:navigate>
                    {{ __('Perfil') }}
                </x-dropdown-link>

                <button wire:click="logout" class="w-full text-start">
                    <x-dropdown-link>
                        {{ __('Cerrar sesión') }}
                    </x-dropdown-link>
                </button>
            </x-slot>
        </x-dropdown>
    </div>
</header>
