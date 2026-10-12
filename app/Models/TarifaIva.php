<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TarifaIva extends Model
{
    protected $table = 'tarifas_iva';

    protected $primaryKey = 'codigo_porcentaje';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['porcentaje' => 'decimal:2', 'activo' => 'boolean'];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public static function paraCodigo(string $codigo): self
    {
        return static::activas()->find($codigo)
            ?? throw new \DomainException('Seleccione una tarifa de IVA activa del catálogo.');
    }
}
