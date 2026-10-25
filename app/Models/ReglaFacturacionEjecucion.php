<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaFacturacionEjecucion extends Model
{
    protected $table = 'regla_facturacion_ejecuciones';

    public $timestamps = false;

    protected $fillable = ['cliente_identificacion', 'periodo', 'factura_id', 'estado', 'modo_emision', 'error_mensaje', 'ejecutado_at'];

    protected $casts = ['ejecutado_at' => 'datetime'];

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaFacturacion::class, 'regla_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
}
