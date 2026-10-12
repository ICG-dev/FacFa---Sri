<?php

namespace App\Policies;

use App\Models\Factura;
use App\Models\User;
use App\Services\Sri\AnulacionElegibilidadService;

class FacturaPolicy
{
    public function __construct(
        private readonly AnulacionElegibilidadService $anulacionElegibilidad,
    ) {}

    




    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['facturador', 'contador', 'admin']);
    }

    public function view(User $user, Factura $factura): bool
    {
        return $user->empresa_id === $factura->empresa_id
            && $user->hasAnyRole(['facturador', 'contador', 'admin']);
    }

    public function descargarRide(User $user, Factura $factura): bool
    {
        return $this->view($user, $factura)
            && in_array($factura->estado, [Factura::ESTADO_AUTORIZADA, Factura::ESTADO_ANULADA], true);
    }

    public function descargarXml(User $user, Factura $factura): bool
    {
        return $this->view($user, $factura)
            && (in_array($factura->estado, [Factura::ESTADO_AUTORIZADA, Factura::ESTADO_ANULADA], true)
                || $factura->xml_autorizado || $factura->xml_firmado || $factura->xml_generado);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['facturador', 'admin']);
    }

    


    public function update(User $user, Factura $factura): bool
    {
        return $user->empresa_id === $factura->empresa_id
            && $user->hasAnyRole(['facturador', 'admin'])
            && ($user->hasRole('admin') || $factura->user_id === $user->id)
            && in_array($factura->estado, [Factura::ESTADO_BORRADOR, Factura::ESTADO_DEVUELTA, Factura::ESTADO_RECHAZADA], true);
    }

    public function reabrirBorrador(User $user, Factura $factura): bool
    {
        return in_array($factura->estado, [Factura::ESTADO_DEVUELTA, Factura::ESTADO_RECHAZADA], true)
            && $this->update($user, $factura);
    }

    public function delete(User $user, Factura $factura): bool
    {
        return $this->esBorradorPropioDeLaEmpresa($user, $factura)
            && $user->hasAnyRole(['facturador', 'admin']);
    }

    


    public function confirmar(User $user, Factura $factura): bool
    {
        return $this->esBorradorPropioDeLaEmpresa($user, $factura)
            && $user->hasAnyRole(['facturador', 'admin']);
    }

    


    public function anularBorrador(User $user, Factura $factura): bool
    {
        return $this->esBorradorPropioDeLaEmpresa($user, $factura)
            && $user->hasAnyRole(['facturador', 'admin']);
    }

    






    public function solicitarAnulacion(User $user, Factura $factura): bool
    {
        return $user->empresa_id === $factura->empresa_id
            && $user->hasRole('admin')
            && $factura->estado === Factura::ESTADO_AUTORIZADA
            && $this->anulacionElegibilidad->via($factura) === AnulacionElegibilidadService::VIA_EN_LINEA;
    }

    private function esBorradorPropioDeLaEmpresa(User $user, Factura $factura): bool
    {
        return $user->empresa_id === $factura->empresa_id
            && $factura->user_id === $user->id
            && $factura->estado === Factura::ESTADO_BORRADOR;
    }
}
