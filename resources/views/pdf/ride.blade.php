<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RIDE - {{ $numero_factura }}</title>
    <style>
        @page { margin: 24px 26px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #000; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .header { table-layout: fixed; margin-bottom: 18px; }
        .header > tbody > tr > td { width: 50%; padding: 0; }
        .header .left { padding-right: 12px; }
        .box { border: 1px solid #000; border-radius: 8px; padding: 8px; overflow-wrap: break-word; }
        .logo { height: 112px; text-align: center; padding-top: 18px; color: #e00000; font-size: 27px; font-weight: bold; }
        .emisor { min-height: 190px; }
        .emisor-name { font-size: 11px; font-weight: bold; margin-bottom: 18px; }
        .line { margin-bottom: 10px; }
        .label { font-weight: bold; }
        .title { font-size: 18px; margin: 12px 0; }
        .number { font-size: 11px; margin-bottom: 16px; }
        .document { min-height: 320px; }
        .authorization { font-family: "DejaVu Sans Mono", monospace; font-size: 8px; font-weight: bold; word-wrap: break-word; margin: 14px 0; }
        .access { margin-top: 16px; }
        .barcode { display: block; width: 100%; height: 42px; margin-top: 14px; }
        .access-key { text-align: center; font-family: "DejaVu Sans Mono", monospace; font-size: 7px; margin-top: 3px; }
        .buyer { margin-bottom: 8px; }
        .buyer td { padding: 3px 0; word-wrap: break-word; }
        .details { table-layout: fixed; margin-bottom: 6px; font-size: 8px; }
        .details thead { display: table-header-group; }
        .details th, .details td { border: 1px solid #000; padding: 5px 3px; word-wrap: break-word; }
        .details th { font-weight: normal; vertical-align: middle; text-align: center; }
        .details tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary { table-layout: fixed; }
        .summary > tbody > tr > td { padding: 0; }
        .summary .additional-column { width: 60%; padding-right: 14px; }
        .summary .totals-column { width: 40%; }
        .additional { margin-bottom: 8px; border: 1px solid #000; page-break-inside: avoid; }
        .section-title { text-align: center; padding: 4px; border-bottom: 1px solid #000; }
        .additional td { padding: 4px 6px; word-wrap: break-word; }
        .additional .field { width: 24%; }
        .payments th, .payments td, .totals td { border: 1px solid #000; padding: 4px; word-wrap: break-word; }
        .payments th { font-weight: normal; }
        .totals { font-size: 8px; }
        .totals td:first-child { width: 74%; }
        .totals tr { page-break-inside: avoid; }
        .total { font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 8px; }
        .anulada { margin-bottom: 10px; padding: 8px; border: 2px solid #991b1b; color: #991b1b; font-size: 24px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    @if ($factura->estado === \App\Models\Factura::ESTADO_ANULADA)
        <div class="anulada">ANULADA</div>
    @endif
    <table class="header">
        <tbody><tr>
            <td class="left">
                <div class="logo">NO TIENE LOGO</div>
                <div class="box emisor">
                    <div class="emisor-name">{{ mb_strtoupper($emisor['razon_social']) }}</div>
                    @if ($emisor['nombre_comercial'])
                        <div class="line">{{ $emisor['nombre_comercial'] }}</div>
                    @endif
                    <div class="line"><span class="label">Dirección Matriz:</span> {{ $emisor['dir_matriz'] }}</div>
                    <div class="line"><span class="label">Dirección Sucursal:</span> {{ $emisor['dir_establecimiento'] }}</div>
                    @if ($emisor['contribuyente_especial'])
                        <div class="line"><span class="label">Contribuyente Especial Nro:</span> {{ $emisor['contribuyente_especial'] }}</div>
                    @endif
                    <div class="line"><span class="label">OBLIGADO A LLEVAR CONTABILIDAD:</span> {{ $emisor['obligado_contabilidad'] }}</div>
                    @if ($emisor['rimpe'])
                        <div>{{ $emisor['rimpe'] }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="box document">
                    <div style="font-size: 13px"><span class="label">R.U.C.:</span> {{ $emisor['ruc'] }}</div>
                    <div class="title">FACTURA</div>
                    <div class="number">No. {{ $numero_factura }}</div>
                    <div>NÚMERO DE AUTORIZACIÓN</div>
                    <div class="authorization">{{ $factura->numero_autorizacion }}</div>
                    <div class="line"><span class="label">FECHA Y HORA DE AUTORIZACIÓN:</span><br>{{ $factura->fecha_autorizacion->format('d/m/Y H:i:s') }}</div>
                    <div class="line"><span class="label">AMBIENTE:</span> {{ $ambiente }}</div>
                    <div class="line"><span class="label">EMISIÓN:</span> {{ $tipo_emision }}</div>
                    <div><span class="label">Estado:</span> {{ strtoupper($factura->estado) }}</div>
                    <div class="access">
                        <div>CLAVE DE ACCESO</div>
                        <img class="barcode" src="{{ $barcode }}" alt="Código de barras Code128 de la clave de acceso">
                        <div class="access-key">{{ $factura->clave_acceso }}</div>
                    </div>
                </div>
            </td>
        </tr></tbody>
    </table>

    <div class="box buyer">
        <table>
            <tr><td colspan="3"><span class="label">Razón Social / Nombres y Apellidos:</span> {{ $factura->razon_social_comprador }}</td></tr>
            <tr><td colspan="3"><span class="label">Identificación:</span> {{ $factura->identificacion_comprador }}</td></tr>
            <tr>
                <td><span class="label">Fecha:</span> {{ $factura->fecha_emision->format('d/m/Y') }}</td>
                <td><span class="label">Placa / Matrícula:</span> {{ $placa ?? '-' }}</td>
                <td><span class="label">Guía:</span> {{ $factura->guia_remision ?? '-' }}</td>
            </tr>
            <tr><td colspan="3"><span class="label">Direccion:</span> {{ $factura->direccion_comprador ?? '-' }}</td></tr>
        </table>
    </div>

    <table class="details">
        <thead><tr>
            <th style="width: 7%">Cod. Principal</th>
            <th style="width: 7%">Cod. Auxiliar</th>
            <th style="width: 9%">Cantidad</th>
            <th style="width: 23%">Descripción</th>
            <th style="width: 12%">Detalle Adicional</th>
            <th style="width: 10%">Precio Unitario</th>
            <th style="width: 7%">Subsidio</th>
            <th style="width: 9%">Precio sin Subsidio</th>
            <th style="width: 7%">Descuento</th>
            <th style="width: 9%">Precio Total</th>
        </tr></thead>
        <tbody>
            @foreach ($factura->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->codigo_principal }}</td>
                    <td>{{ $detalle->codigo_auxiliar }}</td>
                    <td class="right">{{ $detalle->cantidad }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td>{{ $detalle->detalle_adicional }}</td>
                    <td class="right">{{ $detalle->precio_unitario }}</td>
                    <td class="right">0.00</td>
                    <td class="right">0.00</td>
                    <td class="right">{{ $detalle->descuento }}</td>
                    <td class="right">{{ $detalle->precio_total_sin_impuesto }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tbody><tr>
            <td class="additional-column">
                <div class="additional">
                    <div class="section-title">Información Adicional</div>
                    <table>
                        <tr><td class="field">Telefono:</td><td>{{ $factura->telefono_comprador ?? '-' }}</td></tr>
                        <tr><td class="field">Email:</td><td>{{ $factura->correo_comprador ?? '-' }}</td></tr>
                        <tr><td class="field">Direccion:</td><td>{{ $factura->direccion_comprador ?? '-' }}</td></tr>
                        @foreach ($adicionales as $campo)
                            <tr><td class="field">{{ $campo['nombre'] }}:</td><td>{{ $campo['valor'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
                <table class="payments">
                    <thead><tr><th>Forma de pago</th><th>Valor</th></tr></thead>
                    <tbody>
                        @foreach ($pagos as $pago)
                            <tr><td>{{ $pago['codigo'] }} - {{ $pago['descripcion'] }}</td><td class="right">{{ $pago['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td class="totals-column">
                <table class="totals">
                    @foreach ($totales as $total)
                        <tr @class(['total' => $total['etiqueta'] === 'VALOR TOTAL'])>
                            <td>{{ $total['etiqueta'] }}</td><td class="right">{{ $total['valor'] }}</td>
                        </tr>
                    @endforeach
                    <tr><td>VALOR TOTAL SIN SUBSIDIO</td><td class="right">0.00</td></tr>
                    <tr><td>AHORRO POR SUBSIDIO:<br>(Incluye IVA cuando corresponda)</td><td class="right">0.00</td></tr>
                </table>
            </td>
        </tr></tbody>
    </table>
    <div class="footer">RIDE - Representación impresa del documento electrónico autorizado.</div>
</body>
</html>
