<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'estado',
        'ambiente',
        'fecha_emision',
        'tipo_identificacion_comprador',
        'identificacion_comprador',
        'razon_social_comprador',
        'direccion_comprador',
        'telefono_comprador',
        'correo_comprador',
        'guia_remision',
        'factura_comercial_negociable',
        'total_sin_impuestos',
        'total_descuento',
        'importe_total',
        'forma_pago',
        'establecimiento',
        'punto_emision',
        'secuencial',
        'clave_acceso',
        'xml_generado',
        'xml_firmado',
        'xml_autorizado',
        'numero_autorizacion',
        'fecha_autorizacion',
        'enviado_at',
        'fecha_anulacion',
        'mensajes_error',
    ];

    protected $casts = [
        'ambiente' => 'integer',
        'fecha_emision' => 'date',
        'fecha_autorizacion' => 'datetime',
        'enviado_at' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'mensajes_error' => 'array',
        'factura_comercial_negociable' => 'boolean',
        'total_sin_impuestos' => 'decimal:2',
        'total_descuento' => 'decimal:2',
        'importe_total' => 'decimal:2',
    ];

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ENVIADA = 'enviada';
    public const ESTADO_AUTORIZADA = 'autorizada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_DEVUELTA = 'devuelta';
    public const ESTADO_PENDIENTE_ANULAR = 'pendiente_anular';
    public const ESTADO_ANULADA = 'anulada';

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    public function ambienteEmision(): int
    {
        if ($this->ambiente !== null) {
            return $this->ambiente;
        }

        
        $ambiente = substr($this->clave_acceso ?? '', 23, 1);
        if (strlen($this->clave_acceso ?? '') === 49 && in_array($ambiente, ['1', '2'], true)) {
            return (int) $ambiente;
        }

        throw new \DomainException('No se puede determinar el ambiente de emisión de la factura.');
    }

    public function esConsumidorFinal(): bool
    {
        return $this->identificacion_comprador === config('sri.identificacion_consumidor_final');
    }
}
