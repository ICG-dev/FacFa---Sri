<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaFacturacionCliente extends Model
{
    protected $table = 'regla_facturacion_clientes';

    protected $fillable = ['tipo_identificacion', 'identificacion', 'razon_social', 'direccion', 'telefono', 'correo', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaFacturacion::class, 'regla_id');
    }
}
