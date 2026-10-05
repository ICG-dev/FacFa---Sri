<?php

namespace App\Services\Sri;

use App\Models\Empresa;
use SoapClient;








class SriConsultaComprobanteClient
{
    public function consultarEstadoAutorizacion(string $claveAcceso, Empresa $empresa, ?int $ambiente = null): string
    {
        $cliente = $this->clienteSoap($empresa, $ambiente);

        $respuesta = $cliente->consultarEstadoAutorizacionComprobante($claveAcceso);
        $resultado = $respuesta->EstadoAutorizacionComprobante ?? $respuesta;

        return (string) ($resultado->estadoAutorizacion ?? $resultado->estadoConsulta ?? 'DESCONOCIDO');
    }

    private function clienteSoap(Empresa $empresa, ?int $ambiente = null): SoapClient
    {
        $ambiente ??= $empresa->ambiente;
        $wsdl = config("sri.ambientes.{$ambiente}.consulta");

        return new SoapClient($wsdl, [
            'connection_timeout' => config('sri.soap_timeout'),
            'exceptions' => true,
        ]);
    }
}
