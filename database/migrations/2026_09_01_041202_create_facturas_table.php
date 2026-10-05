<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('estado', 20)->default('borrador');
            $table->char('clave_acceso', 49)->nullable()->unique();
            $table->string('establecimiento', 3)->nullable();
            $table->string('punto_emision', 3)->nullable();
            $table->unsignedBigInteger('secuencial')->nullable();

            $table->date('fecha_emision');
            $table->string('tipo_identificacion_comprador', 2);
            $table->string('identificacion_comprador', 20);
            $table->string('razon_social_comprador', 300);
            $table->string('direccion_comprador', 300)->nullable();

            $table->decimal('total_sin_impuestos', 14, 2)->default(0);
            $table->decimal('total_descuento', 14, 2)->default(0);
            $table->decimal('importe_total', 14, 2)->default(0);
            $table->unsignedTinyInteger('forma_pago')->nullable();

            $table->longText('xml_generado')->nullable();
            $table->longText('xml_firmado')->nullable();
            $table->longText('xml_autorizado')->nullable();
            $table->string('numero_autorizacion', 49)->nullable();
            $table->timestampTz('fecha_autorizacion')->nullable();
            $table->timestampTz('enviado_at')->nullable();
            $table->timestampTz('fecha_anulacion')->nullable();
            $table->jsonb('mensajes_error')->nullable();

            $table->timestamps();

            $table->unique(['empresa_id', 'establecimiento', 'punto_emision', 'secuencial']);
            $table->index(['empresa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
