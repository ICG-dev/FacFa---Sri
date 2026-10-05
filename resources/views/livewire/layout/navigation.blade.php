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

    public function tituloActual(): string
    {
        return match (true) {
            request()->routeIs('dashboard') => __('Dashboard'),
            request()->routeIs('facturas.create') => __('Nueva factura'),
            request()->routeIs('facturas.edit') => __('Editar factura'),
            request()->routeIs('facturas.show') => __('Factura'),
            request()->routeIs('facturas.*') => __('Facturas'),
            request()->routeIs('profile') => __('Perfil'),
            default => config('app.name'),
        };
    }
}; ?>

<header class="flex items-center gap-2 md:gap-4 px-3 md:px-6 py-3 flex-shrink-0 z-10 bg-card border-b border-border">
    <button @click="mobileOpen = true" class="p-1.5 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors lg:hidden">
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>
    <button @click="sidebarOpen = ! sidebarOpen" class="p-1.5 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors hidden lg:block">
        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>

    <div class="hidden sm:flex items-center gap-1.5 min-w-0 text-sm text-muted-foreground">
        <span class="hidden md:inline">{{ __('Sistema') }}</span>
        <svg class="h-3.5 w-3.5 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        <span class="truncate font-semibold text-foreground">{{ $this->tituloActual() }}</span>
    </div>
    <span class="sm:hidden font-heading font-semibold text-sm text-foreground truncate">{{ $this->tituloActual() }}</span>

    <div class="flex-1"></div>

    <button
        onclick="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';"
        class="p-2 rounded-lg text-muted-foreground bg-secondary hover:opacity-80 transition-opacity"
        title="{{ __('Cambiar tema') }}"
    >
        <svg class="h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
        <svg class="h-4 w-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
    </button>

    <button class="relative p-2 rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors" title="{{ __('Notificaciones') }}">
        <svg class="h-[17px] w-[17px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
    </button>

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
