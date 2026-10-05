<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
