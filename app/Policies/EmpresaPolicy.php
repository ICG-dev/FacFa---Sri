<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    


    public function viewAny(User $user): bool
    {
        return $user->esSuperAdmin();
    }

    


    public function view(User $user, Empresa $empresa): bool
    {
        return $user->esSuperAdmin()
            || ($user->empresa_id === $empresa->id && $user->hasRole('admin'));
    }

    


    public function create(User $user): bool
    {
        return $user->esSuperAdmin();
    }

    




    public function update(User $user, Empresa $empresa): bool
    {
        return $user->esSuperAdmin()
            || ($user->empresa_id === $empresa->id && $user->hasRole('admin'));
    }
}
