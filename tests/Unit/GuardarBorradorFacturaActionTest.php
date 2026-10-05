<?php

namespace Tests\Unit;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GuardarBorradorFacturaActionTest extends TestCase
{
    #[Test]
    public function calcula_totales_de_varias_lineas_con_descuento_e_iva(): void
    {
        $accion = new GuardarBorradorFacturaAction;

        $totales = $accion->calcularTotales([
            ['cantidad' => 2, 'precio_unitario' => 50, 'descuento' => 0, 'tarifa' => 15],
            ['cantidad' => 1, 'precio_unitario' => 100, 'descuento' => 10, 'tarifa' => 0],
        ]);

        
        
        $this->assertSame(190.0, $totales['totalSinImpuestos']);
        $this->assertSame(10.0, $totales['totalDescuento']);
        $this->assertSame(15.0, $totales['totalImpuesto']);
        $this->assertSame(205.0, $totales['importeTotal']);
    }

    #[Test]
    public function una_lista_vacia_de_lineas_da_totales_en_cero(): void
    {
        $accion = new GuardarBorradorFacturaAction;

        $totales = $accion->calcularTotales([]);

        $this->assertEquals(0, $totales['importeTotal']);
    }

    #[Test]
    public function el_subsidio_se_descuenta_del_precio_unitario_antes_de_multiplicar_por_cantidad(): void
    {
        $accion = new GuardarBorradorFacturaAction;

        $totales = $accion->calcularTotales([
            ['cantidad' => 2, 'precio_unitario' => 50, 'subsidio' => 10, 'descuento' => 0, 'tarifa' => 0],
        ]);

        
        $this->assertSame(80.0, $totales['totalSinImpuestos']);
        $this->assertSame(80.0, $totales['importeTotal']);
    }

    #[Test]
    public function redondea_cada_linea_antes_de_sumar_los_totales(): void
    {
        $totales = (new GuardarBorradorFacturaAction)->calcularTotales([
            ['cantidad' => 1, 'precio_unitario' => 1.005, 'tarifa' => 15],
            ['cantidad' => 1, 'precio_unitario' => 1.005, 'tarifa' => 15],
        ]);

        $this->assertSame(2.02, $totales['totalSinImpuestos']);
        $this->assertSame(0.30, $totales['totalImpuesto']);
        $this->assertSame(2.32, $totales['importeTotal']);
    }


    #[Test]
    public function rechaza_tarifas_fuera_del_catalogo_sin_truncar_decimales(): void
    {
        $this->expectException(\DomainException::class);
        (new GuardarBorradorFacturaAction)->calcularTotales([
            ['cantidad' => 1, 'precio_unitario' => 10, 'tarifa' => 5.5],
        ]);
    }
}
