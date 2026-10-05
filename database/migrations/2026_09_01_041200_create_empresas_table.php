<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 13)->unique();
            $table->string('razon_social', 300);
            $table->string('nombre_comercial', 300)->nullable();
            $table->string('dir_matriz', 300);
            $table->string('contribuyente_especial', 13)->nullable();
            $table->boolean('obligado_contabilidad')->default(false);
            $table->string('establecimiento', 3)->default('001');
            $table->string('punto_emision', 3)->default('001');
            $table->unsignedBigInteger('ultimo_secuencial')->default(0);
            $table->unsignedTinyInteger('ambiente')->default(1);
            $table->string('certificado_path')->nullable();
            $table->text('certificado_password')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
