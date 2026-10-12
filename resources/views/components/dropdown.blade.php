@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-popover', 'floating' => false])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{
        open: false, top: 0, left: 0,
        posicionar() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const panel = this.$refs.panel;
            this.left = Math.max(8, Math.min(window.innerWidth - panel.offsetWidth - 8, rect.right - panel.offsetWidth));
            this.top = rect.bottom + panel.offsetHeight + 16 > window.innerHeight
                ? Math.max(8, rect.top - panel.offsetHeight - 8)
                : rect.bottom + 8;
            panel.querySelector('a, button')?.focus();
        }
    }"
    @if ($floating)
        x-effect="if (open) $nextTick(() => posicionar())"
        x-on:scroll.window.capture="open = false" x-on:resize.window="open = false"
    @endif
    x-on:keydown.escape.stop.prevent="open = false; $refs.trigger.querySelector('button')?.focus()" @click.outside="open = false" @close.stop="open = false">
    <div x-ref="trigger" @click="open = ! open">
        {{ $trigger }}
    </div>

    @if ($floating)
        <template x-teleport="body">
    @endif
    <div x-ref="panel" x-show="open"
            @if ($floating)
                x-bind:style="{ top: top + 'px', left: left + 'px' }"
                x-on:click.outside="open = false"
            @endif
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="{{ $floating ? 'fixed max-h-[calc(100vh-1rem)] overflow-y-auto' : 'absolute mt-2 '.$alignmentClasses }} z-50 {{ $width }} rounded-md shadow-lg"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md border border-border text-popover-foreground {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
    @if ($floating)
        </template>
    @endif
</div>
