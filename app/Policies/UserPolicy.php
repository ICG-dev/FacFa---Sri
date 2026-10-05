<?php

namespace App\Policies;

use App\Models\User;







class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esSuperAdmin() || $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->esSuperAdmin()
            || ($user->hasRole('admin') && $user->empresa_id === $model->empresa_id);
    }

    public function create(User $user): bool
    {
        return $user->esSuperAdmin() || $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->esSuperAdmin()
            || ($user->hasRole('admin') && $user->empresa_id === $model->empresa_id);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->esSuperAdmin()
            || ($user->hasRole('admin') && $user->empresa_id === $model->empresa_id);
    }

    





    public function asignarRol(User $user, User $model, string $rol): bool
    {
        if ($user->esSuperAdmin()) {
            return true;
        }

        return $user->hasRole('admin')
            && $user->empresa_id === $model->empresa_id
            && in_array($rol, ['facturador', 'contador', 'admin'], true);
    }
}
