<?php

use Brick\Math\BigDecimal;
use Database\Seeders\TarifaIvaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $codigos = ['0.00' => '0', '5.00' => '5', '8.00' => '8', '12.00' => '2',
            '13.00' => '10', '14.00' => '3', '15.00' => '4'];

        // Comprobar antes de modificar el esquema: una tarifa desconocida exige revisión, nunca pérdida de datos.
        foreach (DB::table('productos')->select('id', 'tarifa')->cursor() as $producto) {
            $tarifa = (string) BigDecimal::of((string) $producto->tarifa)->toScale(2);
            if (! array_key_exists($tarifa, $codigos)) {
                throw new RuntimeException("Tarifa histórica desconocida en producto {$producto->id}: {$tarifa}.");
            }
        }

        Schema::create('tarifas_iva', function (Blueprint $table) {
            $table->string('codigo_porcentaje', 4)->primary();
            $table->decimal('porcentaje', 5, 2);
            $table->string('descripcion', 150);
            $table->boolean('activo')->default(true);
        });
        (new TarifaIvaSeeder)->run();

        DB::table('tipos_identificacion')->insertOrIgnore([
            'codigo' => '08', 'descripcion' => 'Identificación del exterior',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_porcentaje', 4)->nullable()->after('codigo_impuesto');
        });

        foreach ($codigos as $tarifa => $codigo) {
            DB::table('productos')->where('tarifa', $tarifa)->update(['codigo_porcentaje' => $codigo]);
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_porcentaje', 4)->nullable(false)->change();
        });

        // factura_detalles ya tiene codigo_porcentaje obligatorio: conservarlo, especialmente 0/6/7.
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('codigo_porcentaje');
        });
        Schema::dropIfExists('tarifas_iva');
    }
};
