<?php

use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNAS = [
        'productos' => ['precio_unitario'],
        'factura_detalles' => ['cantidad', 'precio_unitario', 'subsidio', 'precio_sin_subsidio'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as $nombre => $columnas) {
            Schema::table($nombre, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as $columna) {
                    // SQLite aplica afinidad REAL a DECIMAL: conservar texto evita perder dígitos en las pruebas.
                    $definicion = DB::connection()->getDriverName() === 'sqlite'
                        ? $table->string($columna, 20)
                        : $table->decimal($columna, 18, 6);
                    if ($columna === 'subsidio') {
                        $definicion->default(0);
                    }
                    $definicion->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Impedir un rollback que trunque cantidades o precios ya guardados con más de dos decimales.
        foreach (self::COLUMNAS as $nombre => $columnas) {
            foreach (DB::table($nombre)->select(['id', ...$columnas])->cursor() as $fila) {
                foreach ($columnas as $columna) {
                    try {
                        BigDecimal::of((string) $fila->$columna)->toScale(2);
                    } catch (RoundingNecessaryException $e) {
                        throw new RuntimeException("No se puede reducir precisión: {$nombre}:{$fila->id}, {$columna}.", 0, $e);
                    }
                    if (BigDecimal::of((string) $fila->$columna)->abs()->isGreaterThan('9999999999.99')) {
                        throw new RuntimeException("Valor fuera del rango original: {$nombre}:{$fila->id}, {$columna}.");
                    }
                }
            }
        }
        foreach (self::COLUMNAS as $nombre => $columnas) {
            Schema::table($nombre, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as $columna) {
                    $definicion = $table->decimal($columna, 12, 2);
                    if ($columna === 'subsidio') {
                        $definicion->default(0);
                    }
                    $definicion->change();
                }
            });
        }
    }
};
