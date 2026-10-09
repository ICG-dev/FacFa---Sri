<x-slot:header>
    <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
        {{ $factura ? __('Editar factura (borrador)') : __('Nueva factura') }}
    </h1>
</x-slot>

<div class="max-w-5xl">
            <form wire:submit="guardar" class="bg-card text-card-foreground border border-border rounded-lg p-6 space-y-8">

                <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="fecha_emision" :value="__('Fecha de emisión')" />
                        <x-text-input wire:model="fecha_emision" id="fecha_emision" type="date" class="block w-full" />
                        <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="forma_pago" :value="__('Forma de pago')" />
                        <select wire:model="forma_pago" id="forma_pago" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                            @foreach ($this->formasPago as $forma)
                                <option value="{{ $forma->codigo }}">{{ sprintf('%02d', $forma->codigo) }} - {{ $forma->descripcion }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('forma_pago')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="guia_remision" :value="__('Guía de remisión')" />
                        <x-text-input wire:model="guia_remision" id="guia_remision" type="text" class="block w-full" placeholder="{{ __('Opcional') }}" />
                        <x-input-error :messages="$errors->get('guia_remision')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input wire:model="factura_comercial_negociable" id="factura_comercial_negociable" type="checkbox" class="rounded border-border text-primary shadow-sm focus:ring-ring" />
                        <label for="factura_comercial_negociable" class="text-sm text-foreground">{{ __('Factura comercial negociable') }}</label>
                    </div>

                    <div>
                        <x-input-label for="tipo_identificacion_comprador" :value="__('Tipo de identificación del comprador')" />
                        <select wire:model="tipo_identificacion_comprador" id="tipo_identificacion_comprador" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                            @foreach ($this->tiposIdentificacion as $tipo)
                                <option value="{{ $tipo->codigo }}">{{ $tipo->descripcion }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo_identificacion_comprador')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="identificacion_comprador" :value="__('Identificación del comprador')" />
                        <x-text-input wire:model="identificacion_comprador" id="identificacion_comprador" type="text" class="block w-full" />
                        <x-input-error :messages="$errors->get('identificacion_comprador')" class="mt-2" />
                        @if ($tipo_identificacion_comprador === '07')
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Usa 9999999999999 para consumidor final. Ojo: estas facturas no podrán anularse una vez autorizadas.') }}</p>
                        @endif
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="razon_social_comprador" :value="__('Razón social del comprador')" />
                        <x-text-input wire:model="razon_social_comprador" id="razon_social_comprador" type="text" class="block w-full" />
                        <x-input-error :messages="$errors->get('razon_social_comprador')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="direccion_comprador" :value="__('Dirección del comprador')" />
                        <x-text-input wire:model="direccion_comprador" id="direccion_comprador" type="text" class="block w-full" />
                        <x-input-error :messages="$errors->get('direccion_comprador')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="telefono_comprador" :value="__('Teléfono del comprador')" />
                        <x-text-input wire:model="telefono_comprador" id="telefono_comprador" type="text" class="block w-full" placeholder="{{ __('Opcional') }}" />
                        <x-input-error :messages="$errors->get('telefono_comprador')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="correo_comprador" :value="__('Correo electrónico del comprador')" />
                        <x-text-input wire:model="correo_comprador" id="correo_comprador" type="email" class="block w-full" />
                        <x-input-error :messages="$errors->get('correo_comprador')" class="mt-2" />
                    </div>
                </section>

                <section>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-medium text-foreground">{{ __('Detalle') }}</h3>
                        <x-secondary-button type="button" wire:click="agregarLinea">{{ __('Agregar ítem') }}</x-secondary-button>
                    </div>
                    <p class="text-xs text-muted-foreground mb-3">
                        {{ __('Escribí en "Código" para buscar en tu catálogo de productos.') }}
                        @can('create', \App\Models\Producto::class)
                            <a href="{{ route('productos.index') }}" wire:navigate class="text-accent hover:underline font-medium">{{ __('Administrar catálogo →') }}</a>
                        @endcan
                    </p>

                    <x-input-error :messages="$errors->get('lineas')" class="mb-2" />

                    <div class="space-y-3">
                        @foreach ($lineas as $indice => $linea)
                            <div wire:key="linea-{{ $indice }}" class="border border-border rounded-md p-3 space-y-2">
                                <div class="grid grid-cols-12 gap-2 items-start">
                                    <div class="col-span-2 relative">
                                        <x-input-label :value="__('Código')" class="text-xs" />
                                        <x-text-input
                                            wire:model.live.debounce.300ms="lineas.{{ $indice }}.codigo_principal"
                                            wire:blur.debounce.200ms="cerrarSugerencias({{ $indice }})"
                                            type="text" class="block w-full text-sm" autocomplete="off"
                                        />

                                        @if (! empty($sugerenciasProductos[$indice]))
                                            <div class="absolute z-20 mt-1 w-64 max-w-[16rem] rounded-md border border-border bg-popover text-popover-foreground shadow-lg overflow-hidden">
                                                @foreach ($sugerenciasProductos[$indice] as $producto)
                                                    <button
                                                        type="button"
                                                        wire:click="seleccionarProducto({{ $indice }}, {{ $producto->id }})"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-accent hover:text-accent-foreground transition-colors border-b border-border last:border-b-0"
                                                    >
                                                        <div class="font-mono font-semibold">{{ $producto->codigo_principal }}</div>
                                                        <div class="text-muted-foreground truncate">{{ $producto->descripcion }} — ${{ \App\Services\Sri\FacturaCalculoService::formatear($producto->precio_unitario) }}</div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('Cód. auxiliar')" class="text-xs" />
                                        <x-text-input wire:model="lineas.{{ $indice }}.codigo_auxiliar" type="text" class="block w-full text-sm" />
                                    </div>
                                    <div class="col-span-4">
                                        <x-input-label :value="__('Descripción')" class="text-xs" />
                                        <x-text-input wire:model="lineas.{{ $indice }}.descripcion" type="text" class="block w-full text-sm" />
                                    </div>
                                    <div class="col-span-3">
                                        <x-input-label :value="__('Detalle adicional')" class="text-xs" />
                                        <x-text-input wire:model="lineas.{{ $indice }}.detalle_adicional" type="text" class="block w-full text-sm" />
                                    </div>
                                    <div class="col-span-1 flex items-end justify-end h-full pt-5">
                                        <button type="button" wire:click="quitarLinea({{ $indice }})" class="text-destructive text-sm hover:underline">
                                            {{ __('Quitar') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-2 items-start">
                                    <div class="col-span-2">
                                        <x-input-label :value="__('Cant.')" class="text-xs" />
                                        <x-text-input wire:model.live.debounce.500ms="lineas.{{ $indice }}.cantidad" type="number" step="0.000001" class="block w-full text-sm" />
                                        <x-input-error :messages="$errors->get('lineas.'.$indice.'.cantidad')" class="mt-1" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('Precio unit.')" class="text-xs" />
                                        <x-text-input wire:model.live.debounce.500ms="lineas.{{ $indice }}.precio_unitario" type="number" step="0.000001" class="block w-full text-sm" />
                                        <x-input-error :messages="$errors->get('lineas.'.$indice.'.precio_unitario')" class="mt-1" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('Subsidio')" class="text-xs" />
                                        <x-text-input wire:model.live.debounce.500ms="lineas.{{ $indice }}.subsidio" type="number" step="0.000001" class="block w-full text-sm" />
                                        <x-input-error :messages="$errors->get('lineas.'.$indice.'.subsidio')" class="mt-1" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('Desc.')" class="text-xs" />
                                        <x-text-input wire:model.live.debounce.500ms="lineas.{{ $indice }}.descuento" type="number" step="0.01" class="block w-full text-sm" />
                                        <x-input-error :messages="$errors->get('lineas.'.$indice.'.descuento')" class="mt-1" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('IVA %')" class="text-xs" />
                                        <select wire:model.live="lineas.{{ $indice }}.codigo_porcentaje" class="block w-full text-sm rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                                            @foreach ($this->tarifasIva as $tarifa)
                                                <option value="{{ $tarifa->codigo_porcentaje }}">{{ $tarifa->descripcion }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('lineas.'.$indice.'.codigo_porcentaje')" class="mt-1" />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label :value="__('P. sin subsidio')" class="text-xs" />
                                        <p class="text-sm text-muted-foreground pt-1.5">
                                            ${{ \App\Services\Sri\FacturaCalculoService::formatear($this->calculo['detalles'][$indice]['precio_sin_subsidio'] ?? '0') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="border-t border-border pt-4">
                    <dl class="space-y-1 text-sm max-w-xs ms-auto">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">{{ __('Subtotal') }}</dt>
                            <dd class="text-foreground">${{ $this->totales['totalSinImpuestos'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">{{ __('Descuento') }}</dt>
                            <dd class="text-foreground">${{ $this->totales['totalDescuento'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">{{ __('IVA') }}</dt>
                            <dd class="text-foreground">${{ $this->totales['totalImpuesto'] }}</dd>
                        </div>
                        <div class="flex justify-between font-semibold text-base pt-1 border-t border-border">
                            <dt class="text-foreground">{{ __('Total') }}</dt>
                            <dd class="text-foreground">${{ $this->totales['importeTotal'] }}</dd>
                        </div>
                    </dl>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('facturas.index') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">
                        {{ __('Cancelar') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Guardar borrador') }}</x-primary-button>
                </div>
            </form>
</div>
