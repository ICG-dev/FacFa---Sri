<x-slot:header><h1 class="font-heading font-bold text-xl text-foreground">{{ $regla ? 'Editar regla' : 'Nueva regla de facturación' }}</h1></x-slot>
<form wire:submit="guardar" class="space-y-6 text-foreground">
    <x-input-error :messages="$errors->all()" />
    <section class="rounded-lg border border-border bg-card p-5 space-y-4">
        <h2 class="font-semibold text-lg">Datos generales</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <label class="text-sm">Nombre<x-text-input wire:model="nombre" class="w-full" required maxlength="150" /></label>
            <label class="text-sm">Emisión<select wire:model="tipo_emision" class="w-full rounded-md border-border bg-input-background text-foreground"><option value="revision">Revisión — generar borrador</option><option value="automatica">Automática SRI — firmar y enviar</option></select></label>
            <label class="text-sm">Frecuencia<select wire:model.live="frecuencia" class="w-full rounded-md border-border bg-input-background text-foreground">@foreach(['unica' => 'Única', 'diaria' => 'Diaria', 'semanal' => 'Semanal', 'mensual' => 'Mensual', 'anual' => 'Anual'] as $valor => $texto)<option value="{{ $valor }}">{{ $texto }}</option>@endforeach</select></label>
            @if(in_array($frecuencia, ['mensual', 'semanal']))<label class="text-sm">{{ $frecuencia === 'semanal' ? 'Día de la semana (1 lunes, 7 domingo)' : 'Día de corte (1 a 28)' }}<x-text-input wire:model="dia_corte" type="number" min="1" :max="$frecuencia === 'semanal' ? 7 : 28" class="w-full" /></label>@endif
            <label class="text-sm">Fecha de inicio<x-text-input wire:model="fecha_inicio" type="date" class="w-full" required /></label>
            <label class="text-sm">Fecha de fin (opcional)<x-text-input wire:model="fecha_fin" type="date" class="w-full" /></label>
            <label class="text-sm">Forma de pago<select wire:model="forma_pago" class="w-full rounded-md border-border bg-input-background text-foreground">@foreach($formasPago as $pago)<option value="{{ str_pad($pago->codigo, 2, '0', STR_PAD_LEFT) }}">{{ $pago->descripcion }}</option>@endforeach</select></label>
            <label class="text-sm">Plazo de crédito (días)<x-text-input wire:model="tiempo_plazo" type="number" min="0" max="3650" class="w-full" /></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="activo" class="rounded border-border text-primary">Regla activa</label>
            <label class="sm:col-span-2 lg:col-span-3 text-sm">Descripción<textarea wire:model="descripcion" class="w-full rounded-md border-border bg-input-background text-foreground" rows="2"></textarea></label>
        </div>
        <p class="text-sm text-muted-foreground">La ejecución programada se realiza a las 06:00, hora de Ecuador. Cada cliente se factura una vez por período.</p>
    </section>
    <section class="rounded-lg border border-border bg-card p-5 space-y-4">
        <h2 class="font-semibold text-lg">Productos</h2>
        <label class="block text-sm">Buscar en el catálogo<x-text-input wire:model.live.debounce.300ms="buscarProducto" class="w-full sm:max-w-md" placeholder="Código o descripción" /></label>
        <div class="flex flex-wrap gap-2">@foreach($catalogo as $producto)<button type="button" wire:click="agregarProducto({{ $producto->id }})" class="text-sm border border-border rounded-md px-3 py-2 hover:bg-muted">+ {{ $producto->descripcion }}</button>@endforeach</div>
        <div class="space-y-3">
            @foreach($productos as $i => $producto)
                <div wire:key="linea-{{ $i }}-{{ $producto['producto_id'] }}" class="border border-border rounded-md p-3 grid sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    <p class="text-sm font-medium break-words">{{ $producto['descripcion'] ?? 'Producto' }}</p>
                    <label class="text-sm">Cantidad<x-text-input wire:model.live.debounce.300ms="productos.{{ $i }}.cantidad" type="number" min="0.000001" step="0.000001" class="w-full" /></label>
                    <label class="text-sm">Precio unitario<x-text-input wire:model.live.debounce.300ms="productos.{{ $i }}.precio_unitario" type="number" min="0" step="0.000001" class="w-full" /></label>
                    <label class="text-sm">Descuento<x-text-input wire:model.live.debounce.300ms="productos.{{ $i }}.descuento" type="number" min="0" step="0.01" class="w-full" /></label>
                    <label class="text-sm">IVA<select wire:model.live="productos.{{ $i }}.codigo_porcentaje" class="w-full rounded-md border-border bg-input-background text-foreground">@foreach($tarifas as $tarifa)<option value="{{ $tarifa->codigo_porcentaje }}">{{ $tarifa->descripcion }}</option>@endforeach</select></label>
                    <button type="button" class="text-sm text-destructive underline py-2" wire:click="quitarProducto({{ $i }})">Quitar producto</button>
                </div>
            @endforeach
        </div>
        <dl class="flex flex-wrap justify-end gap-5 text-sm"><div><dt>Subtotal</dt><dd>${{ $this->totales['totalSinImpuestos'] }}</dd></div><div><dt>IVA</dt><dd>${{ $this->totales['totalImpuesto'] }}</dd></div><div class="font-bold"><dt>Total por cliente</dt><dd>${{ $this->totales['importeTotal'] }}</dd></div></dl>
    </section>
    <section class="rounded-lg border border-border bg-card p-5 space-y-4">
        <h2 class="font-semibold text-lg">Clientes</h2>
        <label class="block text-sm">Buscar clientes de facturas anteriores<x-text-input wire:model.live.debounce.300ms="buscarCliente" class="w-full sm:max-w-md" placeholder="Identificación o razón social" /></label>
        @foreach($anteriores as $anterior)<button type="button" wire:click="seleccionarCliente({{ $anterior->id }})" class="block text-sm underline">{{ $anterior->identificacion_comprador }} — {{ $anterior->razon_social_comprador }}</button>@endforeach
        @foreach($clientes as $i => $cliente)
            <fieldset wire:key="cliente-{{ $i }}" class="border border-border rounded-md p-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <legend class="px-2 text-sm font-medium">Cliente {{ $i + 1 }}</legend>
                <label class="text-sm">Tipo de identificación<select wire:model="clientes.{{ $i }}.tipo_identificacion" class="w-full rounded-md border-border bg-input-background text-foreground"><option value="04">RUC</option><option value="05">Cédula</option><option value="06">Pasaporte</option><option value="07">Consumidor final</option></select></label>
                @foreach(['identificacion' => 'Identificación', 'razon_social' => 'Razón social', 'direccion' => 'Dirección', 'correo' => 'Correo', 'telefono' => 'Teléfono (opcional)'] as $campo => $label)
                    <label class="text-sm">{{ $label }}<x-text-input wire:model="clientes.{{ $i }}.{{ $campo }}" :type="$campo === 'correo' ? 'email' : 'text'" class="w-full" /></label>
                @endforeach
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="clientes.{{ $i }}.activo" class="rounded border-border text-primary">Incluir en la facturación</label>
                <button type="button" class="text-sm text-destructive underline text-left" wire:click="quitarCliente({{ $i }})">Quitar cliente</button>
            </fieldset>
        @endforeach
        <x-secondary-button type="button" wire:click="agregarCliente">Agregar cliente manual</x-secondary-button>
    </section>
    <div class="flex justify-end items-center gap-4"><a wire:navigate class="text-sm underline" href="{{ route('reglas-facturacion.index') }}">Cancelar</a><x-primary-button wire:loading.attr="disabled">Guardar regla</x-primary-button></div>
</form>
