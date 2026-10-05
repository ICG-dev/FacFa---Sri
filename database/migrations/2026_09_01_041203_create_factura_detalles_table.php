<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();

            $table->string('codigo_principal', 25);
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('precio_total_sin_impuesto', 14, 2);

            $table->unsignedTinyInteger('codigo_impuesto')->default(2);
            $table->string('codigo_porcentaje', 4);
            $table->decimal('tarifa', 5, 2)->default(0);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('valor_impuesto', 14, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
