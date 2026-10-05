<x-slot:header>
    <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
        {{ $usuario ? __('Editar usuario') : __('Nuevo usuario') }}
    </h1>
</x-slot>

<div class="max-w-xl">
    <form wire:submit="guardar" class="bg-card text-card-foreground border border-border rounded-lg p-6 space-y-6">

        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input wire:model="name" id="name" type="text" class="block w-full" autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" type="email" class="block w-full" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="$usuario ? __('Nueva contraseña (dejar en blanco para no cambiarla)') : __('Contraseña')" />
            <x-text-input wire:model="password" id="password" type="password" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        @if (auth()->user()->esSuperAdmin() && $rol !== 'super_admin')
            <div>
                <x-input-label for="empresa_id" :value="__('Empresa')" />
                <select wire:model="empresa_id" id="empresa_id" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                    <option value="">{{ __('Selecciona una empresa') }}</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->razon_social }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('empresa_id')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="rol" :value="__('Rol')" />
            <select wire:model.live="rol" id="rol" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                @foreach ($this->rolesDisponibles() as $opcion)
                    <option value="{{ $opcion }}">{{ ucfirst($opcion) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('rol')" class="mt-2" />
            @if ($rol === 'super_admin')
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Un super_admin administra la plataforma y no pertenece a ninguna empresa.') }}</p>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('usuarios.index') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">
                {{ __('Cancelar') }}
            </a>
            <x-primary-button type="submit">{{ __('Guardar usuario') }}</x-primary-button>
        </div>
    </form>
</div>
