<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            $table->string('codigo_auxiliar', 25)->nullable()->after('codigo_principal');
            $table->string('detalle_adicional', 300)->nullable()->after('descripcion');
            $table->decimal('subsidio', 12, 2)->default(0)->after('precio_unitario');
            $table->decimal('precio_sin_subsidio', 12, 2)->nullable()->after('subsidio');
        });

        
        DB::table('factura_detalles')->update(['precio_sin_subsidio' => DB::raw('precio_unitario')]);

        Schema::table('factura_detalles', function (Blueprint $table) {
            $table->decimal('precio_sin_subsidio', 12, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_auxiliar',
                'detalle_adicional',
                'subsidio',
                'precio_sin_subsidio',
            ]);
        });
    }
};
