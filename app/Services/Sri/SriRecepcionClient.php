<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use SoapClient;





class SriRecepcionClient
{
    


    public function enviar(string $xmlFirmado, Empresa $empresa, ?int $ambiente = null): array
    {
        $cliente = $this->clienteSoap($empresa, $ambiente);

        $respuesta = $cliente->validarComprobante(['xml' => $xmlFirmado]);
        $resultado = $respuesta->RespuestaRecepcionComprobante ?? $respuesta;

        $mensajes = [];
        if (! empty($resultado->comprobantes->comprobante)) {
            $comprobantes = is_array($resultado->comprobantes->comprobante)
                ? $resultado->comprobantes->comprobante
                : [$resultado->comprobantes->comprobante];

            foreach ($comprobantes as $comprobante) {
                $mensajesComprobante = $comprobante->mensajes->mensaje ?? [];
                $mensajesComprobante = is_array($mensajesComprobante) ? $mensajesComprobante : [$mensajesComprobante];

                foreach ($mensajesComprobante as $mensaje) {
                    $mensajes[] = [
                        'identificador' => $mensaje->identificador ?? null,
                        'mensaje' => $mensaje->mensaje ?? '',
                        'informacionAdicional' => $mensaje->informacionAdicional ?? null,
                        'tipo' => $mensaje->tipo ?? null,
                    ];
                }
            }
        }

        return [
            'estado' => (string) $resultado->estado,
            'mensajes' => $mensajes,
        ];
    }

    private function clienteSoap(Empresa $empresa, ?int $ambiente = null): SoapClient
    {
        $ambiente ??= $empresa->ambiente;
        $wsdl = config("sri.ambientes.{$ambiente}.recepcion");

        return new SoapClient($wsdl, [
            'connection_timeout' => config('sri.soap_timeout'),
            'exceptions' => true,
        ]);
    }
}
