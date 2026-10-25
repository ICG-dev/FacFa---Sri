<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_emision', ['revision', 'automatica']);
            $table->enum('frecuencia', ['unica', 'diaria', 'semanal', 'mensual', 'anual']);
            $table->unsignedTinyInteger('dia_corte')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamp('proxima_ejecucion_at')->index();
            $table->string('forma_pago', 2)->default('01');
            $table->unsignedInteger('tiempo_plazo')->nullable();
            $table->string('unidad_tiempo', 20)->default('dias');
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('regla_facturacion_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_id')->constrained('reglas_facturacion')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 12, 6)->default(1);
            $table->decimal('precio_unitario', 14, 6);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->integer('codigo_impuesto')->default(2);
            $table->string('codigo_porcentaje', 4);
            $table->decimal('tarifa', 5, 2);
            $table->timestamps();
        });
        Schema::create('regla_facturacion_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_id')->constrained('reglas_facturacion')->cascadeOnDelete();
            $table->string('tipo_identificacion', 2);
            $table->string('identificacion', 20);
            $table->string('razon_social', 300);
            $table->string('direccion', 300)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['regla_id', 'identificacion']);
        });
        Schema::create('regla_facturacion_ejecuciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_id')->constrained('reglas_facturacion')->cascadeOnDelete();
            $table->string('cliente_identificacion', 20);
            $table->string('periodo', 20);
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            $table->enum('estado', ['exitoso', 'fallido']);
            $table->enum('modo_emision', ['revision', 'automatica']);
            $table->text('error_mensaje')->nullable();
            $table->timestamp('ejecutado_at');
            $table->unique(['regla_id', 'cliente_identificacion', 'periodo'], 'regla_cliente_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regla_facturacion_ejecuciones');
        Schema::dropIfExists('regla_facturacion_clientes');
        Schema::dropIfExists('regla_facturacion_productos');
        Schema::dropIfExists('reglas_facturacion');
    }
};
