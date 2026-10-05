<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use SoapClient;










class SriAutorizacionClient
{
    


    public function consultar(string $claveAcceso, Empresa $empresa, ?int $ambiente = null): array
    {
        $cliente = $this->clienteSoap($empresa, $ambiente);

        $respuesta = $cliente->autorizacionComprobante($claveAcceso);
        $resultado = $respuesta->RespuestaAutorizacionComprobante ?? $respuesta;

        $autorizaciones = $resultado->autorizaciones->autorizacion ?? null;
        if (is_null($autorizaciones)) {
            return [
                'estado' => 'NO_ENCONTRADO',
                'numeroAutorizacion' => null,
                'fechaAutorizacion' => null,
                'xmlAutorizado' => null,
                'mensajes' => [],
            ];
        }

        
        $autorizacion = is_array($autorizaciones) ? end($autorizaciones) : $autorizaciones;

        $mensajesRespuesta = $autorizacion->mensajes->mensaje ?? [];
        $mensajesRespuesta = is_array($mensajesRespuesta) ? $mensajesRespuesta : [$mensajesRespuesta];

        $mensajes = [];
        foreach ($mensajesRespuesta as $mensaje) {
            if (! is_object($mensaje)) {
                continue;
            }
            $mensajes[] = [
                'identificador' => $mensaje->identificador ?? null,
                'mensaje' => $mensaje->mensaje ?? '',
                'tipo' => $mensaje->tipo ?? null,
            ];
        }

        return [
            'estado' => (string) $autorizacion->estado,
            'numeroAutorizacion' => $autorizacion->numeroAutorizacion ?? null,
            'fechaAutorizacion' => $autorizacion->fechaAutorizacion ?? null,
            'xmlAutorizado' => $autorizacion->comprobante ?? null,
            'mensajes' => $mensajes,
        ];
    }

    private function clienteSoap(Empresa $empresa, ?int $ambiente = null): SoapClient
    {
        $ambiente ??= $empresa->ambiente;
        $wsdl = config("sri.ambientes.{$ambiente}.autorizacion");

        return new SoapClient($wsdl, [
            'connection_timeout' => config('sri.soap_timeout'),
            'exceptions' => true,
        ]);
    }
}
