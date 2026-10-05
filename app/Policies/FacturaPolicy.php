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

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['facturador', 'admin']);
    }

    


    public function update(User $user, Factura $factura): bool
    {
        return $this->esBorradorPropioDeLaEmpresa($user, $factura)
            && $user->hasAnyRole(['facturador', 'admin']);
    }

    public function delete(User $user, Factura $factura): bool
    {
        return $this->update($user, $factura);
    }

    


    public function confirmar(User $user, Factura $factura): bool
    {
        return $this->update($user, $factura);
    }

    


    public function anularBorrador(User $user, Factura $factura): bool
    {
        return $this->update($user, $factura);
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
