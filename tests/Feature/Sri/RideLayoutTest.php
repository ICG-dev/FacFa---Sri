<?php

namespace Tests\Feature\Sri;

use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Services\Sri\RideGenerator;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RideLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function presenta_los_campos_y_totales_del_formato_sri(): void
    {
        $factura = Factura::factory()->autorizada()->create(['ambiente' => 1, 'clave_acceso' => '0410202601179001234500110010020000001231234567810', 'importe_total' => '115.00']);
        FacturaDetalle::factory()->for($factura)->create([
            'codigo_auxiliar' => 'AUX-123', 'detalle_adicional' => 'Detalle del servicio',
            'codigo_porcentaje' => '4', 'tarifa' => '15.00',
            'base_imponible' => '100.00', 'valor_impuesto' => '15.00',
        ]);

        $html = view('pdf.ride', app(RideGenerator::class)->datos($factura))->render();
        $doc = new DOMDocument;
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($doc);
        $this->assertSame(10, $xpath->query('//table[@class="details"]/thead/tr/th')->length);
        foreach (['NO TIENE LOGO', 'AUX-123', 'Detalle del servicio', 'Información Adicional',
            'SUBTOTAL 15%', 'SUBTOTAL 0%', 'SUBTOTAL NO OBJETO DE IVA', 'SUBTOTAL EXENTO DE IVA',
            'ICE', 'IVA 15%', 'IRBPNR', 'PROPINA', 'VALOR TOTAL SIN SUBSIDIO', 'AHORRO POR SUBSIDIO:',
        ] as $campo) {
            $this->assertStringContainsString($campo, $html);
        }
        $this->assertSame(1, $xpath->query('//table[@class="header"]//img[contains(@src,"data:image/svg+xml;base64,")]')->length);
        $this->assertSame('115.00', trim($xpath->evaluate('string(//table[@class="totals"]//tr[td[1]="VALOR TOTAL"]/td[2])')));
        foreach (['SUBTOTAL 0%', 'SUBTOTAL NO OBJETO DE IVA', 'SUBTOTAL EXENTO DE IVA', 'ICE', 'IRBPNR', 'PROPINA',
            'VALOR TOTAL SIN SUBSIDIO',
        ] as $campo) {
            $this->assertSame('0.00', trim($xpath->evaluate('string(//table[@class="totals"]//tr[td[1]="'.$campo.'"]/td[2])')));
        }
    }

    #[Test]
    public function obtiene_rimpe_placa_propina_y_adicionales_del_xml_guardado_y_los_escapa(): void
    {
        $factura = Factura::factory()->autorizada()->create([
            'ambiente' => 1,
            'clave_acceso' => '0410202601179001234500110010020000001231234567810',
            'xml_autorizado' => '<factura><infoTributaria><contribuyenteRimpe>CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE</contribuyenteRimpe></infoTributaria><infoFactura><placa>ABC-1234</placa><propina>2.50</propina></infoFactura><infoAdicional><campoAdicional nombre="Pedido">&lt;script&gt;privado&lt;/script&gt;</campoAdicional></infoAdicional></factura>',
        ]);

        $html = view('pdf.ride', app(RideGenerator::class)->datos($factura))->render();
        foreach (['CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE', 'ABC-1234', 'Pedido', '&lt;script&gt;privado&lt;/script&gt;', '2.50'] as $valor) {
            $this->assertStringContainsString($valor, $html);
        }
        $this->assertStringNotContainsString('<script>', $html);
    }
}
