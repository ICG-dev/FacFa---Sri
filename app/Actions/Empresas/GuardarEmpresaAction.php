<?php

namespace App\Actions\Empresas;

use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;





class GuardarEmpresaAction
{
    


    public function ejecutar(?Empresa $empresa, array $datos, ?UploadedFile $certificado, ?string $certificadoPassword): Empresa
    {
        if ($certificado && ! openssl_pkcs12_read($certificado->get(), $credenciales, $certificadoPassword ?? '')) {
            throw ValidationException::withMessages([
                'certificado' => 'El certificado PKCS#12 o su contraseña no son válidos.',
            ]);
        }

        $empresaModel = $empresa ?? new Empresa;
        $anterior = $empresaModel->certificado_path;
        $path = null;

        try {
            if ($certificado) {
                
                $path = $certificado->storeAs('certificados', Str::uuid().'.p12', 'local');
                if (! $path) {
                    throw new \RuntimeException('No se pudo guardar el certificado .p12.');
                }
            }

            DB::transaction(function () use ($empresaModel, $datos, $path, $certificadoPassword) {
                $empresaModel->fill($datos);
                if ($path) {
                    $empresaModel->certificado_path = $path;
                    $empresaModel->certificado_password = $certificadoPassword;
                }
                $empresaModel->save();
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        if ($path && $anterior) {
            DB::afterCommit(fn () => Storage::disk('local')->delete($anterior));
        }

        return $empresaModel;
    }
}
