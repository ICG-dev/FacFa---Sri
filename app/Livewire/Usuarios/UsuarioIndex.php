<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class UsuarioIndex extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function eliminar(int $usuarioId): void
    {
        $usuario = $this->usuarioVisible($usuarioId);

        $this->authorize('delete', $usuario);

        $usuario->delete();
    }

    private function usuarioVisible(int $usuarioId): User
    {
        $query = User::query();

        if (! auth()->user()->esSuperAdmin()) {
            $query->where('empresa_id', auth()->user()->empresa_id);
        }

        return $query->findOrFail($usuarioId);
    }

    public function render()
    {
        $query = User::query()->with(['empresa', 'roles']);

        if (! auth()->user()->esSuperAdmin()) {
            $query->where('empresa_id', auth()->user()->empresa_id);
        }

        $usuarios = $query->latest()->paginate(15);

        return view('livewire.usuarios.usuario-index', ['usuarios' => $usuarios]);
    }
}
