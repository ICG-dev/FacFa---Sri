<?php

namespace Tests\Unit;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use App\Models\Empresa;
use App\Models\User;
use App\Services\Sri\FacturaXmlBuilder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaXmlBuilderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function bases_e_impuestos_del_xml_coinciden_con_los_detalles_guardados(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $linea = ['codigo_principal' => 'A', 'descripcion' => 'Producto', 'cantidad' => 1, 'precio_unitario' => 1.005, 'codigo_porcentaje' => '4'];
        $factura = (new GuardarBorradorFacturaAction)->ejecutar(null, $empresa->id, $usuario->id, [
            'fecha_emision' => '2026-10-03',
            'tipo_identificacion_comprador' => '05',
            'identificacion_comprador' => '1712345678',
            'razon_social_comprador' => 'Comprador',
        ], [$linea, $linea]);
        $factura->forceFill(['clave_acceso' => str_repeat('1', 49), 'establecimiento' => '001', 'punto_emision' => '001', 'secuencial' => 1]);
        $doc = new DOMDocument;
        $doc->loadXML((new FacturaXmlBuilder)->build($factura, $empresa));
        $xpath = new DOMXPath($doc);

        $this->assertEquals(2.02, $factura->detalles->sum('precio_total_sin_impuesto'));
        $this->assertEquals($factura->total_sin_impuestos, $xpath->evaluate('sum(//totalImpuesto/baseImponible)'));
        $this->assertEquals($factura->importe_total, round($factura->total_sin_impuestos + $factura->detalles->sum('valor_impuesto'), 2));
        $this->assertEquals($xpath->evaluate('sum(//detalle/impuestos/impuesto/valor)'), $xpath->evaluate('sum(//totalImpuesto/valor)'));
    }
}
