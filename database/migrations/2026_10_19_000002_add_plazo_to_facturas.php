<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedInteger('tiempo_plazo')->nullable();
            $table->string('unidad_tiempo', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facturas', fn (Blueprint $table) => $table->dropColumn(['tiempo_plazo', 'unidad_tiempo']));
    }
};
