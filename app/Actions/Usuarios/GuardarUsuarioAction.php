<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use Illuminate\Support\Facades\Hash;








class GuardarUsuarioAction
{
    


    public function ejecutar(?User $usuario, array $datos, string $rol, ?int $empresaId): User
    {
        $usuarioModel = $usuario ?? new User;

        $usuarioModel->name = $datos['name'];
        $usuarioModel->email = $datos['email'];
        $usuarioModel->empresa_id = $empresaId;

        if (! empty($datos['password'])) {
            $usuarioModel->password = Hash::make($datos['password']);
        }

        $usuarioModel->save();
        $usuarioModel->syncRoles([$rol]);

        return $usuarioModel;
    }
}
