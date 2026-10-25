<?php

namespace App\Policies;

use App\Models\ReglaFacturacion;
use App\Models\User;

class ReglaFacturacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null && $user->hasAnyRole(['admin', 'contador', 'facturador']);
    }

    public function view(User $user, ReglaFacturacion $regla): bool
    {
        return $user->empresa_id === $regla->empresa_id && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null && $user->hasAnyRole(['admin', 'contador']);
    }

    public function update(User $user, ReglaFacturacion $regla): bool
    {
        return $user->empresa_id === $regla->empresa_id && $this->create($user);
    }

    public function delete(User $user, ReglaFacturacion $regla): bool
    {
        return $user->empresa_id === $regla->empresa_id && $user->hasRole('admin');
    }

    public function ejecutar(User $user, ReglaFacturacion $regla): bool
    {
        return $this->update($user, $regla);
    }
}
