<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->string('codigo_principal', 25);
            $table->string('descripcion', 300);
            $table->decimal('precio_unitario', 12, 2);
            $table->unsignedTinyInteger('codigo_impuesto')->default(2);
            $table->decimal('tarifa', 5, 2)->default(15);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo_principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
