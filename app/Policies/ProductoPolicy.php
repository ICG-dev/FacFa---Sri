<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    



    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['facturador', 'admin']);
    }

    public function view(User $user, Producto $producto): bool
    {
        return $user->empresa_id === $producto->empresa_id
            && $user->hasAnyRole(['facturador', 'admin']);
    }

    



    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->empresa_id === $producto->empresa_id && $user->hasRole('admin');
    }

    public function delete(User $user, Producto $producto): bool
    {
        return $this->update($user, $producto);
    }
}
