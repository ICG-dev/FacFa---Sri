<x-slot:header>
    <h1 class="font-heading font-extrabold text-lg md:text-xl text-foreground tracking-tight">
        {{ $empresa ? __('Editar empresa') : __('Nueva empresa') }}
    </h1>
</x-slot>

<div class="max-w-3xl">
    <form wire:submit="guardar" class="bg-card text-card-foreground border border-border rounded-lg p-6 space-y-8">

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="ruc" :value="__('RUC')" />
                <x-text-input wire:model="ruc" id="ruc" type="text" maxlength="13" class="block w-full font-mono" />
                <x-input-error :messages="$errors->get('ruc')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ambiente" :value="__('Ambiente SRI')" />
                <select wire:model="ambiente" id="ambiente" class="block w-full rounded-md border-border bg-input-background text-foreground shadow-sm focus:border-ring focus:ring-ring">
                    <option value="1">{{ __('Pruebas') }}</option>
                    <option value="2">{{ __('Producción') }}</option>
                </select>
                <x-input-error :messages="$errors->get('ambiente')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="razon_social" :value="__('Razón social')" />
                <x-text-input wire:model="razon_social" id="razon_social" type="text" class="block w-full" />
                <x-input-error :messages="$errors->get('razon_social')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="nombre_comercial" :value="__('Nombre comercial (opcional)')" />
                <x-text-input wire:model="nombre_comercial" id="nombre_comercial" type="text" class="block w-full" />
                <x-input-error :messages="$errors->get('nombre_comercial')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="dir_matriz" :value="__('Dirección matriz')" />
                <x-text-input wire:model="dir_matriz" id="dir_matriz" type="text" class="block w-full" />
                <x-input-error :messages="$errors->get('dir_matriz')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="establecimiento" :value="__('Establecimiento')" />
                <x-text-input wire:model="establecimiento" id="establecimiento" type="text" maxlength="3" class="block w-full font-mono" />
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Código de 3 dígitos asignado por el SRI (ej. 001).') }}</p>
                <x-input-error :messages="$errors->get('establecimiento')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="punto_emision" :value="__('Punto de emisión')" />
                <x-text-input wire:model="punto_emision" id="punto_emision" type="text" maxlength="3" class="block w-full font-mono" />
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Código de 3 dígitos para el punto de facturación o caja (ej. 001).') }}</p>
                <x-input-error :messages="$errors->get('punto_emision')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="contribuyente_especial" :value="__('N.º contribuyente especial (opcional)')" />
                <x-text-input wire:model="contribuyente_especial" id="contribuyente_especial" type="text" class="block w-full font-mono" />
                <p class="text-[11px] text-muted-foreground mt-1">{{ __('Número de resolución del SRI si aplica (dejar en blanco si no).') }}</p>
                <x-input-error :messages="$errors->get('contribuyente_especial')" class="mt-2" />
            </div>

            <div class="flex items-center gap-2 mt-6">
                <input wire:model="obligado_contabilidad" id="obligado_contabilidad" type="checkbox" class="rounded border-border text-primary focus:ring-ring">
                <x-input-label for="obligado_contabilidad" :value="__('Obligado a llevar contabilidad')" class="mb-0" />
            </div>
        </section>

        <section class="border-t border-border pt-6 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-foreground">{{ __('Certificado de firma electrónica (.p12)') }}</h3>
                @if ($empresa?->certificado_path)
                    @php($vencimiento = $empresa->fechaExpiracionCertificado())
                    @if ($vencimiento?->isPast())
                        <p class="text-xs text-destructive mt-1">{{ __('El certificado cargado venció el :fecha. Sube un certificado vigente para poder emitir comprobantes.', ['fecha' => $vencimiento->format('d/m/Y')]) }}</p>
                    @elseif ($vencimiento)
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('Ya hay un certificado cargado (vence el :fecha). Sube uno nuevo solo si quieres reemplazarlo.', ['fecha' => $vencimiento->format('d/m/Y')]) }}</p>
                    @else
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ __('Ya hay un certificado cargado. Sube uno nuevo solo si quieres reemplazarlo.') }}</p>
                    @endif
                @elseif (! $empresa && auth()->user()->esSuperAdmin())
                    <p class="text-xs text-muted-foreground mt-1">{{ __('Es opcional aquí: puedes dejarlo en blanco y que el propio administrador de la empresa lo suba después desde "Mi empresa" — es material sensible de firma legal que la empresa no tiene por qué compartir con la plataforma.') }}</p>
                @else
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ __('Sin certificado todavía: la empresa no podrá firmar ni enviar comprobantes hasta que subas uno.') }}</p>
                @endif
            </div>

            <div>
                <x-input-label for="certificado" :value="__('Archivo .p12')" />
                <input wire:model="certificado" id="certificado" type="file" accept=".p12,.pfx" class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-secondary file:text-secondary-foreground">
                <div wire:loading wire:target="certificado" class="text-xs text-muted-foreground mt-1">{{ __('Subiendo…') }}</div>
                <x-input-error :messages="$errors->get('certificado')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="certificado_password" :value="__('Contraseña del certificado')" />
                <div x-data="{ verPassword: false }" class="relative">
                    <x-text-input wire:model="certificado_password" id="certificado_password" type="password" ::type="verPassword ? 'text' : 'password'" class="block w-full pr-10" autocomplete="new-password" />
                    <button type="button" @click="verPassword = !verPassword" :title="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="verPassword" aria-controls="certificado_password" class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-r-md">
                        <svg x-show="!verPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="verPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.5 5.4A11 11 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a19 19 0 0 1-3.1 3.9M6.4 6.4A20 20 0 0 0 2.25 12s3.75 6.75 9.75 6.75a12 12 0 0 0 5.6-1.4M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('certificado_password')" class="mt-2" />
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('empresas.index') }}" wire:navigate class="text-sm text-muted-foreground hover:underline">
                {{ __('Cancelar') }}
            </a>
            <x-primary-button type="submit">{{ __('Guardar empresa') }}</x-primary-button>
        </div>
    </form>
</div>
