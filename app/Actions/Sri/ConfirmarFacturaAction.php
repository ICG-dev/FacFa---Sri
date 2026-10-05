<?php

namespace App\Actions\Sri;

use App\Jobs\EnviarComprobanteRecepcionJob;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Sri\ClaveAccesoService;
use App\Services\Sri\FacturaXmlBuilder;
use App\Services\Sri\XadesSigner;
use Illuminate\Support\Facades\DB;










class ConfirmarFacturaAction
{
    public function __construct(
        private readonly ClaveAccesoService $claveAcceso,
        private readonly FacturaXmlBuilder $xmlBuilder,
        private readonly XadesSigner $signer,
    ) {}

    public function ejecutar(Factura $factura): Factura
    {
        $factura = DB::transaction(function () use ($factura) {
            $factura = Factura::query()->lockForUpdate()->findOrFail($factura->id);

            if ($factura->estado !== Factura::ESTADO_BORRADOR) {
                throw new \DomainException('La factura ya no está en borrador. Actualice la lista antes de continuar.');
            }

            $empresa = Empresa::query()->lockForUpdate()->findOrFail($factura->empresa_id);

            $factura->ambiente = $empresa->ambiente;

            $empresa->increment('ultimo_secuencial');

            $factura->establecimiento = $empresa->establecimiento;
            $factura->punto_emision = $empresa->punto_emision;
            $factura->secuencial = $empresa->ultimo_secuencial;
            $factura->clave_acceso = $this->claveAcceso->generar($factura, $empresa);

            $xmlGenerado = $this->xmlBuilder->build($factura, $empresa);
            $xmlFirmado = $this->signer->firmar($xmlGenerado, $empresa);

            $factura->xml_generado = $xmlGenerado;
            $factura->xml_firmado = $xmlFirmado;
            $factura->estado = Factura::ESTADO_ENVIADA;
            $factura->enviado_at = now();
            $factura->save();

            return $factura;
        });

        EnviarComprobanteRecepcionJob::dispatch($factura)->onQueue('sri');

        return $factura;
    }
}
