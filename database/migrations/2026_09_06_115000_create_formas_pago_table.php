<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;







return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formas_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('codigo')->unique();
            $table->string('descripcion', 150);
            $table->timestamps();
        });

        DB::table('formas_pago')->insert([
            ['codigo' => 1, 'descripcion' => 'Sin utilización del sistema financiero', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 15, 'descripcion' => 'Compensación de deudas', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 16, 'descripcion' => 'Tarjeta de débito', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 17, 'descripcion' => 'Dinero electrónico', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 18, 'descripcion' => 'Tarjeta prepago', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 19, 'descripcion' => 'Tarjeta de crédito', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 20, 'descripcion' => 'Otros con utilización del sistema financiero', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 21, 'descripcion' => 'Endoso de títulos', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('formas_pago');
    }
};
