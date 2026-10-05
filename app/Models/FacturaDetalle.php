<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'factura_id',
        'codigo_principal',
        'codigo_auxiliar',
        'descripcion',
        'detalle_adicional',
        'cantidad',
        'precio_unitario',
        'subsidio',
        'precio_sin_subsidio',
        'descuento',
        'precio_total_sin_impuesto',
        'codigo_impuesto',
        'codigo_porcentaje',
        'tarifa',
        'base_imponible',
        'valor_impuesto',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subsidio' => 'decimal:2',
        'precio_sin_subsidio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'precio_total_sin_impuesto' => 'decimal:2',
        'tarifa' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'valor_impuesto' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
}
