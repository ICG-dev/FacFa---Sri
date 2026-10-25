<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'dir_matriz',
        'contribuyente_especial',
        'obligado_contabilidad',
        'establecimiento',
        'punto_emision',
        'ambiente',
        'certificado_path',
        'certificado_password',
    ];

    protected $casts = [
        'obligado_contabilidad' => 'boolean',
        'ambiente' => 'integer',
        'ultimo_secuencial' => 'integer',
        'certificado_password' => 'encrypted',
    ];

    protected $hidden = [
        'certificado_password',
    ];

    public function fechaExpiracionCertificado(): ?Carbon
    {
        try {
            if (! $this->certificado_path || ! Storage::disk('local')->exists($this->certificado_path)) {
                return null;
            }

            $contenido = Storage::disk('local')->get($this->certificado_path);
            if (! openssl_pkcs12_read($contenido, $certificados, $this->certificado_password ?? '') || ! isset($certificados['cert'])) {
                return null;
            }

            $datos = openssl_x509_parse($certificados['cert']);

            return isset($datos['validTo_time_t'])
                ? Carbon::createFromTimestamp($datos['validTo_time_t'])
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function reglasFacturacion(): HasMany
    {
        return $this->hasMany(ReglaFacturacion::class);
    }
}
