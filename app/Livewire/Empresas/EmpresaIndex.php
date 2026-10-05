<?php

namespace App\Livewire\Empresas;

use App\Models\Empresa;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class EmpresaIndex extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('viewAny', Empresa::class);
    }

    public function render()
    {
        $empresas = Empresa::query()
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return view('livewire.empresas.empresa-index', ['empresas' => $empresas]);
    }
}
