<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'codigo_principal',
        'descripcion',
        'precio_unitario',
        'codigo_impuesto',
        'codigo_porcentaje',
        'tarifa',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:6',
        'codigo_impuesto' => 'integer',
        'tarifa' => 'decimal:2',
    ];

    public function tarifaIva(): BelongsTo
    {
        return $this->belongsTo(TarifaIva::class, 'codigo_porcentaje', 'codigo_porcentaje');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
