<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;






return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_identificacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 2)->unique();
            $table->string('descripcion', 100);
            $table->timestamps();
        });

        DB::table('tipos_identificacion')->insert([
            ['codigo' => '04', 'descripcion' => 'RUC', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '05', 'descripcion' => 'Cédula', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '06', 'descripcion' => 'Pasaporte', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '07', 'descripcion' => 'Consumidor final', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_identificacion');
    }
};
