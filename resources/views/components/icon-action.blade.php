@props(['href' => null, 'color' => 'primary', 'title'])

@php
    $colores = [
        'primary' => 'text-primary hover:bg-primary/10',
        'emerald' => 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10',
        'destructive' => 'text-destructive hover:bg-destructive/10',
        'muted' => 'text-muted-foreground hover:bg-muted',
    ];

    $clases = 'inline-flex items-center justify-center h-8 w-8 rounded-md transition-colors '.($colores[$color] ?? $colores['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
    </a>
@else
    <button type="button" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $clases]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
    </button>
@endif
