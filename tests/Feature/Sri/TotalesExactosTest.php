<?php

namespace Tests\Feature\Sri;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use App\Services\Sri\FacturaXmlBuilder;
use Database\Seeders\RolesAndPermissionsSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TotalesExactosTest extends TestCase
{
    use RefreshDatabase;

    public static function casos(): array
    {
        return [
            'medio centavo' => [[['1', '1.005', '4']], '1.01', '0.15', '1.16', '0.00'],
            '0.1 mas 0.2' => [[['1', '0.1', '0'], ['1', '0.2', '0']], '0.30', '0.00', '0.30', '0.00'],
            'tres por 33.333333' => [[['3', '33.333333', '4']], '100.00', '15.00', '115.00', '0.00'],
            'redondeo por linea' => [[['1', '0.17', '4'], ['1', '0.19', '4']], '0.36', '0.06', '0.42', '0.00'],
            'muchas lineas pequenas' => [array_fill(0, 200, ['1', '0.03', '4']), '6.00', '0.00', '6.00', '0.00'],
            'precio grande a seis decimales' => [[['0.000001', '999999999.123456', '4']], '1000.00', '150.00', '1150.00', '0.00'],
            'cantidad grande a seis decimales' => [[['999999999.123456', '0.000001', '4']], '1000.00', '150.00', '1150.00', '0.00'],
            'cantidad fraccionaria' => [[['0.000001', '1000000', '4']], '1.00', '0.15', '1.15', '0.00'],
            'descuento y subsidio' => [[['3', '33.333333', '4', '0.50', '0.333333']], '98.50', '14.78', '113.28', '0.50'],
            '0 no objeto exento y gravado' => [[['1', '10', '0'], ['2', '2', '6'], ['1', '3', '7'], ['1', '100', '4', '10']],
                '107.00', '13.50', '120.50', '10.00'],
        ];
    }

    #[Test]
    #[DataProvider('casos')]
    public function formulario_guardado_y_xml_conservan_totales_exactos(array $entradas, string $base, string $iva, string $total, string $descuento): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $usuario->assignRole('facturador');
        $lineas = array_map(fn ($e) => ['codigo_principal' => 'TEST', 'descripcion' => 'Producto sintético',
            'cantidad' => $e[0], 'precio_unitario' => $e[1], 'codigo_porcentaje' => $e[2],
            'descuento' => $e[3] ?? '0.00', 'subsidio' => $e[4] ?? '0.00'], $entradas);
        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)
            ->set('tipo_identificacion_comprador', '06')->set('identificacion_comprador', 'TEST-PASAPORTE')
            ->set('razon_social_comprador', 'Comprador sintético')->set('correo_comprador', 'test@example.com')
            ->set('lineas', $lineas);

        $this->assertSame($base, $form->get('totales')['totalSinImpuestos']);
        $this->assertSame($iva, $form->get('totales')['totalImpuesto']);
        $this->assertSame($total, $form->get('totales')['importeTotal']);
        $form->call('guardar')->assertHasNoErrors();
        $factura = Factura::sole()->load('detalles');
        $this->assertSame($base, $factura->total_sin_impuestos);
        $this->assertSame($descuento, $factura->total_descuento);
        $this->assertSame($total, $factura->importe_total);
        $this->assertSame(str_contains($entradas[0][0], '.') ? rtrim(rtrim($entradas[0][0], '0'), '.') : $entradas[0][0], rtrim(rtrim($factura->detalles->first()->cantidad, '0'), '.') ?: '0');
        $this->assertSame(str_contains($entradas[0][1], '.') ? rtrim(rtrim($entradas[0][1], '0'), '.') : $entradas[0][1], rtrim(rtrim($factura->detalles->first()->precio_unitario, '0'), '.') ?: '0');

        $factura->forceFill(['clave_acceso' => str_repeat('1', 49), 'establecimiento' => '001',
            'punto_emision' => '001', 'secuencial' => 1]);
        $doc = new DOMDocument;
        $doc->loadXML((new FacturaXmlBuilder)->build($factura, $empresa));
        $xpath = new DOMXPath($doc);
        $this->assertSame('2.1.0', $doc->documentElement->getAttribute('version'));
        $this->assertSame($base, $xpath->evaluate('string(/factura/infoFactura/totalSinImpuestos)'));
        $this->assertSame($total, $xpath->evaluate('string(/factura/infoFactura/importeTotal)'));
        $this->assertSame($descuento, $xpath->evaluate('string(/factura/infoFactura/totalDescuento)'));
        $this->assertSame($total, $xpath->evaluate('string(/factura/infoFactura/pagos/pago/total)'));
        $this->assertSame($factura->detalles->first()->cantidad, $xpath->evaluate('string(//detalle[1]/cantidad)'));
        $this->assertSame($factura->detalles->first()->precio_sin_subsidio, $xpath->evaluate('string(//detalle[1]/precioUnitario)'));

        $bases = [];
        $impuestos = [];
        foreach ($xpath->query('//detalle/impuestos/impuesto') as $nodo) {
            $clave = $xpath->evaluate('string(codigo)', $nodo).'|'.$xpath->evaluate('string(codigoPorcentaje)', $nodo);
            $bases[$clave][] = $xpath->evaluate('string(baseImponible)', $nodo);
            $impuestos[$clave][] = $xpath->evaluate('string(valor)', $nodo);
        }
        foreach ($xpath->query('//totalConImpuestos/totalImpuesto') as $nodo) {
            $clave = $xpath->evaluate('string(codigo)', $nodo).'|'.$xpath->evaluate('string(codigoPorcentaje)', $nodo);
            $this->assertSame($this->sumarCentavos($bases[$clave]), $xpath->evaluate('string(baseImponible)', $nodo));
            $this->assertSame($this->sumarCentavos($impuestos[$clave]), $xpath->evaluate('string(valor)', $nodo));
        }
        $this->assertSame($iva, $this->sumarCentavos(array_merge(...array_values($impuestos))));
    }

    private function sumarCentavos(array $valores): string
    {
        $centavos = array_sum(array_map(fn ($v) => (int) str_replace('.', '', $v), $valores));

        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    #[Test]
    public function agrupa_por_impuesto_y_codigo_porcentaje_sin_fusionar_categorias_a_cero(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $totales = (new GuardarBorradorFacturaAction)->calcularTotales([
            ['cantidad' => '1', 'precio_unitario' => '0.10', 'codigo_porcentaje' => '6'],
            ['cantidad' => '1', 'precio_unitario' => '0.20', 'codigo_porcentaje' => '6'],
            ['cantidad' => '1', 'precio_unitario' => '2.00', 'codigo_porcentaje' => '7'],
            ['cantidad' => '1', 'precio_unitario' => '3.00', 'codigo_porcentaje' => '0'],
        ]);
        $this->assertSame([
            ['codigo_impuesto' => 2, 'codigo_porcentaje' => '6', 'base_imponible' => '0.30', 'valor' => '0.00'],
            ['codigo_impuesto' => 2, 'codigo_porcentaje' => '7', 'base_imponible' => '2.00', 'valor' => '0.00'],
            ['codigo_impuesto' => 2, 'codigo_porcentaje' => '0', 'base_imponible' => '3.00', 'valor' => '0.00'],
        ], $totales['totalConImpuestos']);
    }

    #[Test]
    public function rechaza_precision_excesiva_y_no_falla_al_previsualizar_entradas_incompletas(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)
            ->set('lineas.0.precio_unitario', '33.3333333')->call('guardar')
            ->assertHasErrors(['lineas.0.precio_unitario'])
            ->assertSee('Ingrese un valor decimal válido con hasta 6 decimales.');
        $form->set('lineas.0.cantidad', '')->set('lineas.0.precio_unitario', '-')
            ->set('lineas.0.subsidio', '')->set('lineas.0.descuento', '');
        $this->assertSame('0.00', $form->get('totales')['importeTotal']);
        $this->assertDatabaseCount('facturas', 0);
    }
}
