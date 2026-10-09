<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RIDE - {{ $numero_factura }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #253243; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .header { margin-bottom: 12px; table-layout: fixed; }
        .header td { width: 50%; border: 1px solid #bac6d3; padding: 12px; }
        .emisor { font-size: 14px; font-weight: bold; color: #173b60; margin-bottom: 6px; }
        .title { font-size: 18px; font-weight: bold; color: #173b60; margin-bottom: 4px; }
        .number { font-size: 12px; font-weight: bold; margin-bottom: 6px; }
        .label { font-weight: bold; }
        .line { margin-bottom: 4px; }
        .authorization { font-family: "DejaVu Sans Mono", monospace; font-size: 7px; word-wrap: break-word; }
        .section { margin-bottom: 12px; }
        .section-title { color: #fff; background: #173b60; padding: 5px 8px; font-weight: bold; }
        .buyer { border: 1px solid #bac6d3; }
        .buyer td { padding: 4px 8px; }
        .details { table-layout: fixed; margin-bottom: 12px; }
        .details thead { display: table-header-group; }
        .details th { background: #edf2f7; color: #173b60; border: 1px solid #bac6d3; padding: 6px 4px; font-size: 8px; }
        .details td { border: 1px solid #d7dee7; padding: 6px 4px; word-wrap: break-word; }
        .details tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .center { text-align: center; }
        .amount { white-space: nowrap; }
        .summary { table-layout: fixed; }
        .summary > tbody > tr > td { width: 50%; padding: 0; }
        .payments th, .payments td, .totals td { border: 1px solid #d7dee7; padding: 5px 7px; }
        .payments th { font-weight: bold; background: #edf2f7; }
        .totals { margin-left: 12px; width: 96%; }
        .total td { background: #edf2f7; font-weight: bold; font-size: 11px; border-top: 2px solid #173b60; }
        .access { margin-top: 14px; padding: 10px 15px; border: 1px solid #bac6d3; text-align: center; page-break-inside: avoid; }
        .access-key { font-family: "DejaVu Sans Mono", monospace; font-size: 10px; margin: 5px 0 8px; }
        .barcode { width: 360px; height: 50px; }
        .footer { margin-top: 8px; text-align: center; color: #64748b; font-size: 8px; }
        .anulada { margin-bottom: 12px; padding: 8px; border: 2px solid #991b1b; background: #fee2e2; color: #991b1b; font-size: 24px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    @if ($factura->estado === \App\Models\Factura::ESTADO_ANULADA)
        <div class="anulada">ANULADA</div>
    @endif
    <table class="header">
        <tr>
            <td>
                <div class="emisor">{{ $emisor['razon_social'] }}</div>
                @if ($emisor['nombre_comercial'])
                    <div class="line">{{ $emisor['nombre_comercial'] }}</div>
                @endif
                <div class="line"><span class="label">RUC:</span> {{ $emisor['ruc'] }}</div>
                <div class="line"><span class="label">Dirección matriz:</span> {{ $emisor['dir_matriz'] }}</div>
                <div class="line"><span class="label">Dirección establecimiento:</span> {{ $emisor['dir_establecimiento'] }}</div>
                <div class="line"><span class="label">Obligado a llevar contabilidad:</span> {{ $emisor['obligado_contabilidad'] }}</div>
                @if ($emisor['contribuyente_especial'])
                    <div class="line"><span class="label">Contribuyente especial:</span> {{ $emisor['contribuyente_especial'] }}</div>
                @endif
            </td>
            <td>
                <div class="title">FACTURA</div>
                <div class="number">No. {{ $numero_factura }}</div>
                <div class="line"><span class="label">Fecha de emisión:</span> {{ $factura->fecha_emision->format('d/m/Y') }}</div>
                <div class="line"><span class="label">Número de autorización:</span></div>
                <div class="authorization line">{{ $factura->numero_autorizacion }}</div>
                <div class="line"><span class="label">Fecha y hora de autorización:</span><br>{{ $factura->fecha_autorizacion->format('d/m/Y H:i:s') }}</div>
                <div class="line"><span class="label">Ambiente:</span> {{ $ambiente }}</div>
                <div class="line"><span class="label">Tipo de emisión:</span> {{ $tipo_emision }}</div>
                <div><span class="label">Estado:</span> {{ strtoupper($factura->estado) }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">DATOS DEL COMPRADOR</div>
        <table class="buyer">
            <tr>
                <td colspan="2"><span class="label">Razón social / nombres:</span> {{ $factura->razon_social_comprador }}</td>
            </tr>
            <tr>
                <td><span class="label">Identificación:</span> {{ $factura->identificacion_comprador }}</td>
                <td><span class="label">Teléfono:</span> {{ $factura->telefono_comprador ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Dirección:</span> {{ $factura->direccion_comprador ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Correo:</span> {{ $factura->correo_comprador ?? '-' }}</td>
            </tr>
            @if ($factura->guia_remision)
                <tr><td colspan="2"><span class="label">Guía de remisión:</span> {{ $factura->guia_remision }}</td></tr>
            @endif
        </table>
    </div>

    <table class="details">
        <thead>
            <tr>
                <th style="width: 12%">Código</th>
                <th style="width: 36%">Descripción</th>
                <th style="width: 10%">Cantidad</th>
                <th style="width: 14%">Precio unitario</th>
                <th style="width: 12%">Descuento</th>
                <th style="width: 16%">Total sin impuestos</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($factura->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->codigo_principal }}</td>
                    <td>
                        {{ $detalle->descripcion }}
                        @if ($detalle->detalle_adicional)
                            <br>{{ $detalle->detalle_adicional }}
                        @endif
                    </td>
                    <td class="right amount">{{ $detalle->cantidad }}</td>
                    <td class="right amount">{{ $detalle->precio_unitario }}</td>
                    <td class="right amount">{{ $detalle->descuento }}</td>
                    <td class="right amount">{{ $detalle->precio_total_sin_impuesto }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="section-title">FORMAS DE PAGO</div>
                <table class="payments">
                    <thead><tr><th>Descripción</th><th>Total USD</th></tr></thead>
                    <tbody>
                        @foreach ($pagos as $pago)
                            <tr>
                                <td>{{ $pago['codigo'] }} - {{ $pago['descripcion'] }}</td>
                                <td class="right amount">{{ $pago['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td>
                <table class="totals">
                    @foreach ($impuestos as $impuesto)
                        @if ($impuesto['codigo'] === '2')
                            <tr>
                                <td>SUBTOTAL {{ $impuesto['etiqueta'] }}</td>
                                <td class="right amount">{{ $impuesto['base'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr><td>SUBTOTAL SIN IMPUESTOS</td><td class="right amount">{{ $factura->total_sin_impuestos }}</td></tr>
                    <tr><td>TOTAL DESCUENTO</td><td class="right amount">{{ $factura->total_descuento }}</td></tr>
                    @foreach ($impuestos as $impuesto)
                        @if ($impuesto['codigo'] === '2' && $impuesto['valor'] !== '0.00')
                            <tr><td>{{ $impuesto['etiqueta'] }}</td><td class="right amount">{{ $impuesto['valor'] }}</td></tr>
                        @elseif ($impuesto['codigo'] !== '2')
                            <tr><td>{{ $impuesto['etiqueta'] }}</td><td class="right amount">{{ $impuesto['valor'] }}</td></tr>
                        @endif
                    @endforeach
                    <tr><td>TOTAL IVA</td><td class="right amount">{{ $iva_total }}</td></tr>
                    <tr class="total"><td>IMPORTE TOTAL USD</td><td class="right amount">{{ $factura->importe_total }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="access">
        <div class="label">CLAVE DE ACCESO</div>
        <div class="access-key">{{ $factura->clave_acceso }}</div>
        <img class="barcode" src="{{ $barcode }}" alt="Código de barras Code128 de la clave de acceso">
    </div>
    <div class="footer">RIDE - Representación impresa del documento electrónico autorizado.</div>
</body>
</html>
