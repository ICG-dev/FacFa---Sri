<?php

namespace App\Livewire\Empresas;

use App\Actions\Empresas\GuardarEmpresaAction;
use App\Models\Empresa;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmpresaForm extends Component
{
    use WithFileUploads;

    public ?Empresa $empresa = null;

    public string $ruc = '';

    public string $razon_social = '';

    public string $nombre_comercial = '';

    public string $dir_matriz = '';

    public string $contribuyente_especial = '';

    public bool $obligado_contabilidad = false;

    public string $establecimiento = '001';

    public string $punto_emision = '001';

    public int $ambiente = 1;

    public $certificado = null;

    public string $certificado_password = '';

    public function mount(?Empresa $empresa = null): void
    {
        
        
        if ($empresa && $empresa->exists) {
            $this->authorize('update', $empresa);

            $this->empresa = $empresa;
            $this->ruc = $empresa->ruc;
            $this->razon_social = $empresa->razon_social;
            $this->nombre_comercial = $empresa->nombre_comercial ?? '';
            $this->dir_matriz = $empresa->dir_matriz;
            $this->contribuyente_especial = $empresa->contribuyente_especial ?? '';
            $this->obligado_contabilidad = $empresa->obligado_contabilidad;
            $this->establecimiento = $empresa->establecimiento;
            $this->punto_emision = $empresa->punto_emision;
            $this->ambiente = $empresa->ambiente;

            return;
        }

        $this->authorize('create', Empresa::class);
    }

    public function guardar(GuardarEmpresaAction $accion): void
    {
        if ($this->empresa) {
            $this->authorize('update', $this->empresa);

            if (! auth()->user()->hasRole('super_admin')) {
                $this->empresa->refresh();
                foreach (['ruc', 'ambiente', 'establecimiento', 'punto_emision'] as $campo) {
                    $this->{$campo} = $this->empresa->{$campo};
                }
            }
        } else {
            $this->authorize('create', Empresa::class);
        }

        $datos = $this->validate([
            'ruc' => ['required', 'digits:13', Rule::unique('empresas', 'ruc')->ignore($this->empresa?->id)],
            'razon_social' => ['required', 'string', 'max:300'],
            'nombre_comercial' => ['nullable', 'string', 'max:300'],
            'dir_matriz' => ['required', 'string', 'max:300'],
            'contribuyente_especial' => ['nullable', 'string', 'max:13'],
            'obligado_contabilidad' => ['boolean'],
            'establecimiento' => ['required', 'digits:3'],
            'punto_emision' => ['required', 'digits:3'],
            'ambiente' => ['required', 'in:1,2'],
            
            
            'certificado' => ['nullable', 'file', 'max:2048'],
            'certificado_password' => [Rule::requiredIf(fn () => (bool) $this->certificado), 'nullable', 'string'],
        ]);

        $accion->ejecutar(
            $this->empresa,
            [
                'ruc' => $datos['ruc'],
                'razon_social' => $datos['razon_social'],
                'nombre_comercial' => $datos['nombre_comercial'] ?: null,
                'dir_matriz' => $datos['dir_matriz'],
                'contribuyente_especial' => $datos['contribuyente_especial'] ?: null,
                'obligado_contabilidad' => $datos['obligado_contabilidad'],
                'establecimiento' => $datos['establecimiento'],
                'punto_emision' => $datos['punto_emision'],
                'ambiente' => $datos['ambiente'],
            ],
            $this->certificado,
            $this->certificado_password ?: null,
        );

        session()->flash('status', __('Empresa guardada correctamente.'));

        $this->redirectRoute('empresas.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.empresas.empresa-form');
    }
}
