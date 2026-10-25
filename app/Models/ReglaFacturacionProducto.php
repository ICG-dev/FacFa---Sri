<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaFacturacionProducto extends Model
{
    protected $table = 'regla_facturacion_productos';

    protected $fillable = ['producto_id', 'cantidad', 'precio_unitario', 'descuento', 'codigo_impuesto', 'codigo_porcentaje', 'tarifa'];

    protected $casts = ['cantidad' => 'decimal:6', 'precio_unitario' => 'decimal:6', 'descuento' => 'decimal:2', 'tarifa' => 'decimal:2'];

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaFacturacion::class, 'regla_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
