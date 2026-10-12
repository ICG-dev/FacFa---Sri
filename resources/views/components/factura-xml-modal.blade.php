@props(['factura', 'xml', 'numero' => null])

@php
    $numero ??= sprintf('%03d-%03d-%09d', $factura->establecimiento, $factura->punto_emision, $factura->secuencial);
    $xmlFormateado = $xml;
    // El formato es solo visual: nunca alterar el XML firmado al copiar o descargar.
    if (! str_contains(strtoupper($xml), '<!DOCTYPE')) {
        $erroresAnteriores = libxml_use_internal_errors(true);
        try {
            $documento = new \DOMDocument;
            $documento->preserveWhiteSpace = false;
            $documento->formatOutput = true;
            if ($documento->loadXML($xml, LIBXML_NONET)) {
                $documento->encoding = 'UTF-8';
                $xmlFormateado = $documento->saveXML();
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($erroresAnteriores);
        }
    }
@endphp

<dialog
    wire:key="xml-modal-{{ $factura->id }}"
    wire:ignore.self
    role="dialog"
    aria-modal="true"
    aria-labelledby="xml-modal-titulo-{{ $factura->id }}"
    class="m-auto w-[calc(100%-2rem)] max-w-5xl max-h-[90vh] overflow-y-auto rounded-lg border border-border bg-card p-0 text-card-foreground shadow-xl backdrop:bg-black/50"
    x-data="{
        xml: @js($xml),
        copiado: false,
        errorCopia: false,
        focoAnterior: null,
        init() {
            this.focoAnterior = document.activeElement;
            document.body.classList.add('overflow-y-hidden');
            this.$el.showModal();
        },
        destroy() {
            document.body.classList.remove('overflow-y-hidden');
            this.focoAnterior?.focus();
        },
        async copiar() {
            this.copiado = false;
            this.errorCopia = false;
            try {
                await navigator.clipboard.writeText(this.xml);
                this.copiado = true;
            } catch (error) {
                const campo = document.createElement('textarea');
                campo.value = this.xml;
                campo.style.position = 'fixed';
                campo.style.opacity = '0';
                this.$el.appendChild(campo);
                campo.select();
                try {
                    this.copiado = document.execCommand('copy');
                } catch (error) {
                    this.copiado = false;
                } finally {
                    campo.remove();
                    this.$refs.copiar.focus();
                }
                this.errorCopia = !this.copiado;
            }
        }
    }"
    x-on:cancel.prevent="$wire.cerrarXmlModal()"
    x-on:click="if ($event.target === $el) $wire.cerrarXmlModal()"
>
    <div class="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
        <div class="min-w-0">
            <h2 id="xml-modal-titulo-{{ $factura->id }}" class="font-heading text-lg font-bold">{{ __('XML del comprobante') }} — {{ __('Factura') }} {{ $numero }}</h2>
            <p class="mt-2 text-xs text-muted-foreground">{{ __('Clave de acceso') }}</p>
            <p class="mt-1 break-all font-mono text-xs">{{ $factura->clave_acceso ?? '—' }}</p>
        </div>
        <button
            type="button"
            autofocus
            wire:click="cerrarXmlModal"
            aria-label="{{ __('Cerrar modal XML') }}"
            title="{{ __('Cerrar') }}"
            class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-all hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <svg class="h-4 w-4 group-hover:scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="sr-only">{{ __('Cerrar') }}</span>
            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-1.5 hidden group-hover:flex items-center px-2 py-0.5 text-[11px] font-medium text-white bg-slate-900 dark:bg-slate-800 rounded shadow-md whitespace-nowrap z-30">
                {{ __('Cerrar') }}
                <span class="absolute left-1/2 bottom-full -translate-x-1/2 border-4 border-transparent border-b-slate-900 dark:border-b-slate-800"></span>
            </span>
        </button>
    </div>
    <div class="space-y-4 p-4 sm:p-6">
        <div class="flex items-center gap-2">
            <button
                type="button"
                x-ref="copiar"
                x-on:click="copiar()"
                :title="copiado ? @js(__('¡Copiado!')) : @js(__('Copiar XML'))"
                :aria-label="copiado ? @js(__('¡Copiado!')) : @js(__('Copiar XML'))"
                class="group relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-card text-foreground transition-all hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                <svg x-show="!copiado" class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                </svg>
                <svg x-show="copiado" style="display: none;" class="h-4 w-4 text-emerald-500 scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span class="sr-only" x-text="copiado ? @js(__('¡Copiado!')) : @js(__('Copiar XML'))">{{ __('Copiar XML') }}</span>
                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex items-center px-2 py-0.5 text-[11px] font-medium text-white bg-slate-900 dark:bg-slate-800 rounded shadow-md whitespace-nowrap z-30">
                    <span x-text="copiado ? @js(__('¡Copiado!')) : @js(__('Copiar XML'))">{{ __('Copiar XML') }}</span>
                    <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
                </span>
            </button>

            <a
                href="{{ route('facturas.xml', ['factura' => $factura, 'download' => 1]) }}"
                title="{{ __('Descargar XML') }}"
                aria-label="{{ __('Descargar XML') }}"
                class="group relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-card text-foreground transition-all hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6v20h12V6l-4-4ZM14 2v4h4m-6 4v8m-3-3 3 3 3-3" />
                </svg>
                <span class="sr-only">{{ __('Descargar XML') }}</span>
                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex items-center px-2 py-0.5 text-[11px] font-medium text-white bg-slate-900 dark:bg-slate-800 rounded shadow-md whitespace-nowrap z-30">
                    {{ __('Descargar XML') }}
                    <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
                </span>
            </a>

            <a
                href="{{ route('facturas.xml', $factura) }}"
                target="_blank"
                rel="noopener noreferrer"
                title="{{ __('Abrir en pestaña nueva') }}"
                aria-label="{{ __('Abrir en pestaña nueva') }}"
                class="group relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-card text-foreground transition-all hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                <svg class="h-4 w-4 text-muted-foreground group-hover:text-foreground group-hover:scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                <span class="sr-only">{{ __('Abrir en pestaña nueva') }}</span>
                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex items-center px-2 py-0.5 text-[11px] font-medium text-white bg-slate-900 dark:bg-slate-800 rounded shadow-md whitespace-nowrap z-30">
                    {{ __('Abrir en pestaña nueva') }}
                    <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
                </span>
            </a>

            <span class="sr-only" role="status" x-text="copiado ? @js(__('XML copiado al portapapeles')) : ''"></span>
        </div>
        <p x-show="errorCopia" style="display: none" role="alert" class="text-sm text-destructive">{{ __('No se pudo copiar. Selecciona el XML para copiarlo manualmente o descarga el archivo.') }}</p>
        <pre tabindex="0" aria-label="{{ __('Contenido XML') }}" class="max-h-[65vh] overflow-auto rounded-md border border-border bg-muted p-4 text-foreground"><code class="font-mono text-xs">{{ $xmlFormateado }}</code></pre>
    </div>
    <div class="flex justify-end border-t border-border p-4">
        <button
            type="button"
            wire:click="cerrarXmlModal"
            title="{{ __('Cerrar') }}"
            aria-label="{{ __('Cerrar') }}"
            class="group relative inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition-all hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        >
            <svg class="h-4 w-4 group-hover:scale-110 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="sr-only">{{ __('Cerrar') }}</span>
            <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:flex items-center px-2 py-0.5 text-[11px] font-medium text-white bg-slate-900 dark:bg-slate-800 rounded shadow-md whitespace-nowrap z-30">
                {{ __('Cerrar') }}
                <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></span>
            </span>
        </button>
    </div>
</dialog>
