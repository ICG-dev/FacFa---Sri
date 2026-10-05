<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('guia_remision', 20)->nullable()->after('direccion_comprador');
            $table->boolean('factura_comercial_negociable')->default(false)->after('guia_remision');
            $table->string('telefono_comprador', 20)->nullable()->after('direccion_comprador');
            $table->string('correo_comprador', 150)->nullable()->after('telefono_comprador');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'guia_remision',
                'factura_comercial_negociable',
                'telefono_comprador',
                'correo_comprador',
            ]);
        });
    }
};
