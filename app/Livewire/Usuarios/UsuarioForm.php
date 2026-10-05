<?php

namespace App\Livewire\Usuarios;

use App\Actions\Usuarios\GuardarUsuarioAction;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UsuarioForm extends Component
{
    public ?User $usuario = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?int $empresa_id = null;

    public string $rol = 'facturador';

    public function mount(?User $usuario = null): void
    {
        
        
        if ($usuario && $usuario->exists) {
            $this->authorize('update', $usuario);

            $this->usuario = $usuario;
            $this->name = $usuario->name;
            $this->email = $usuario->email;
            $this->empresa_id = $usuario->empresa_id;
            $this->rol = $usuario->getRoleNames()->first() ?? 'facturador';

            return;
        }

        $this->authorize('create', User::class);

        
        
        $this->empresa_id = auth()->user()->esSuperAdmin() ? null : auth()->user()->empresa_id;
    }

    public function rolesDisponibles(): array
    {
        return auth()->user()->esSuperAdmin()
            ? ['facturador', 'contador', 'admin', 'super_admin']
            : ['facturador', 'contador', 'admin'];
    }

    public function guardar(GuardarUsuarioAction $accion): void
    {
        $esNuevo = ! $this->usuario;

        $datos = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->usuario?->id)],
            'password' => [Rule::requiredIf($esNuevo), 'nullable', 'string', 'min:8'],
            'empresa_id' => [
                auth()->user()->esSuperAdmin() && $this->rol !== 'super_admin' ? 'required' : 'nullable',
                'exists:empresas,id',
            ],
            'rol' => ['required', Rule::in($this->rolesDisponibles())],
        ]);

        if ($this->usuario) {
            $this->authorize('update', $this->usuario);
        } else {
            $this->authorize('create', User::class);
        }

        
        
        
        $empresaDestino = $datos['rol'] === 'super_admin' ? null : $datos['empresa_id'];
        $usuarioParaValidar = ($this->usuario ?? new User)->replicate()->forceFill(['empresa_id' => $empresaDestino]);

        if (! auth()->user()->esSuperAdmin()) {
            $this->authorize('asignarRol', [$usuarioParaValidar, $datos['rol']]);
        }

        $accion->ejecutar(
            $this->usuario,
            [
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'] ?: null,
            ],
            $datos['rol'],
            $empresaDestino,
        );

        session()->flash('status', __('Usuario guardado correctamente.'));

        $this->redirectRoute('usuarios.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.usuarios.usuario-form', [
            'empresas' => auth()->user()->esSuperAdmin() ? Empresa::query()->orderBy('razon_social')->get() : collect(),
        ]);
    }
}
