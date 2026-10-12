@props(['href' => null, 'color' => 'primary', 'title'])

@php
    $colores = [
        'primary' => 'text-primary hover:bg-primary/10',
        'emerald' => 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10',
        'destructive' => 'text-destructive hover:bg-destructive/10',
        'muted' => 'text-muted-foreground hover:bg-muted',
    ];

    $clases = 'group relative inline-flex items-center justify-center h-8 w-8 rounded-md transition-colors '.($colores[$color] ?? $colores['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $title }}" x-data="{
        tooltip: false, top: 0, left: 0,
        mostrarTooltip() {
            this.tooltip = true;
            this.$nextTick(() => {
                const rect = this.$el.getBoundingClientRect();
                const ancho = this.$refs.tooltip.offsetWidth;
                this.top = rect.top - 8;
                this.left = Math.max(ancho / 2 + 8, Math.min(window.innerWidth - ancho / 2 - 8, rect.left + rect.width / 2));
            });
        }
    }"
    x-on:mouseenter="mostrarTooltip()" x-on:mouseleave="tooltip = false"
    x-on:focus="mostrarTooltip()" x-on:blur="tooltip = false" x-on:click="tooltip = false"
    x-on:scroll.window.capture="tooltip = false" x-on:resize.window="tooltip = false" {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
        <span x-ref="tooltip" x-show="tooltip" x-transition.opacity.duration.150ms style="display: none"
            x-bind:style="{ top: top + 'px', left: left + 'px' }" aria-hidden="true"
            class="pointer-events-none fixed -translate-x-1/2 -translate-y-full z-[60] rounded bg-slate-900 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-white shadow-md whitespace-nowrap">
            {{ $title }}
            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
        </span>
    </a>
@else
    <button type="button" title="{{ $title }}" aria-label="{{ $title }}" x-data="{
        tooltip: false, top: 0, left: 0,
        mostrarTooltip() {
            this.tooltip = true;
            this.$nextTick(() => {
                const rect = this.$el.getBoundingClientRect();
                const ancho = this.$refs.tooltip.offsetWidth;
                this.top = rect.top - 8;
                this.left = Math.max(ancho / 2 + 8, Math.min(window.innerWidth - ancho / 2 - 8, rect.left + rect.width / 2));
            });
        }
    }"
    x-on:mouseenter="mostrarTooltip()" x-on:mouseleave="tooltip = false"
    x-on:focus="mostrarTooltip()" x-on:blur="tooltip = false" x-on:click="tooltip = false"
    x-on:scroll.window.capture="tooltip = false" x-on:resize.window="tooltip = false" {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
        <span x-ref="tooltip" x-show="tooltip" x-transition.opacity.duration.150ms style="display: none"
            x-bind:style="{ top: top + 'px', left: left + 'px' }" aria-hidden="true"
            class="pointer-events-none fixed -translate-x-1/2 -translate-y-full z-[60] rounded bg-slate-900 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-white shadow-md whitespace-nowrap">
            {{ $title }}
            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
        </span>
    </button>
@endif
