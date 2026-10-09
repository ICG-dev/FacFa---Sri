<x-slot:header>
    <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
        {{ $producto ? __('Editar producto') : __('Nuevo producto') }}
    </h1>
</x-slot>

<div class="max-w-xl">
    <form wire:submit="guardar" class="bg-card text-card-foreground border border-border rounded-lg p-6 space-y-6">

        <div>
            <x-input-label for="codigo_principal" :value="__('Código')" />
            <x-text-input wire:model="codigo_principal" id="codigo_principal" type="text" class="block w-full font-mono" autofocus />
            <x-input-error :messages="$errors->get('codigo_principal')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="descripcion" :value="__('Descripción')" />
            <x-text-input wire:model="descripcion" id="descripcion" type="text" class="block w-full" />
            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="precio_unitario" :value="__('Precio unitario')" />
            <x-text-input wire:model="precio_unitario" id="precio_unitario" type="number" step="0.000001" class="block w-full" />
            <x-input-error :messages="$errors->get('precio_unitario')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="codigo_porcentaje" :value="__('IVA')" />
            <select wire:model="codigo_porcentaje" id="codigo_porcentaje" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                @foreach ($this->tarifasIva as $tarifa)
                    <option value="{{ $tarifa->codigo_porcentaje }}">{{ $tarifa->descripcion }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('codigo_porcentaje')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('productos.index') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">
                {{ __('Cancelar') }}
            </a>
            <x-primary-button type="submit">{{ __('Guardar producto') }}</x-primary-button>
        </div>
    </form>
</div>
