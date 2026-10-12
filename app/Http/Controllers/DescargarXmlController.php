<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DescargarXmlController extends Controller
{
    public function __invoke(Request $request, Factura $factura): Response
    {
        Gate::authorize('descargarXml', $factura);

        $xml = $factura->xml_autorizado ?: $factura->xml_firmado ?: $factura->xml_generado;
        abort_unless($xml, 404);

        $numero = str_pad((string) $factura->establecimiento, 3, '0', STR_PAD_LEFT)
            .'-'.str_pad((string) $factura->punto_emision, 3, '0', STR_PAD_LEFT)
            .'-'.str_pad((string) $factura->secuencial, 9, '0', STR_PAD_LEFT);
        $disposicion = $request->boolean('download') ? 'attachment' : 'inline';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => $disposicion.'; filename="FACTURA-'.$numero.'.xml"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
