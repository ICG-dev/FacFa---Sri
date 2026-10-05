









<div
    x-data="{ show: false, titulo: '', mensaje: '' }"
    x-on:alerta-error.window="
        titulo = $event.detail.titulo ?? '{{ __('Ups, algo salió mal') }}';
        mensaje = $event.detail.mensaje ?? '{{ __('Ocurrió un error inesperado.') }}';
        show = true;
    "
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    style="display: none;"
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        @click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-foreground/50"></div>
    </div>

    <div
        x-show="show"
        class="relative w-full max-w-sm bg-card text-card-foreground border border-border rounded-lg overflow-hidden shadow-xl transform transition-all p-6 text-center"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-destructive/10 mb-4">
            <svg class="w-7 h-7 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </div>
        <h3 class="font-heading text-lg font-bold text-foreground mb-2" x-text="titulo"></h3>
        <p class="text-sm text-muted-foreground mb-6" x-text="mensaje"></p>
        <button
            @click="show = false"
            class="inline-flex items-center justify-center h-9 px-6 rounded-md bg-accent text-accent-foreground text-sm font-semibold hover:bg-accent/90 transition-colors"
        >
            {{ __('Entendido') }}
        </button>
    </div>
</div>
