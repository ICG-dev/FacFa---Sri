<?php

namespace Tests\Feature\Sri;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\User;
use App\Services\Sri\RideGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RideDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->for(Empresa::factory())->create();
        $this->usuario->assignRole('facturador');
    }

    #[Test]
    public function una_factura_autorizada_descarga_un_pdf_real(): void
    {
        $factura = $this->facturaAutorizada();
        FacturaDetalle::factory()->for($factura)->create();

        $response = $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/ride');

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('RIDE-001-002-000000123.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $html = view('pdf.ride', app(RideGenerator::class)->datos($factura))->render();
        $this->assertStringContainsString('Estado:</span> AUTORIZADA', $html);
        $this->assertStringNotContainsString('ANULADA', $html);
    }

    #[Test]
    public function una_factura_anulada_descarga_un_pdf_y_muestra_la_marca_anulada(): void
    {
        $factura = $this->facturaAutorizada(['estado' => Factura::ESTADO_ANULADA]);

        $response = $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/ride');

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $html = view('pdf.ride', app(RideGenerator::class)->datos($factura))->render();
        $this->assertStringContainsString('ANULADA', $html);
        $this->assertStringNotContainsString('Estado:</span> AUTORIZADA', $html);
    }

    #[Test]
    #[DataProvider('estadosNoAutorizados')]
    public function una_factura_no_autorizada_no_permite_descargar_el_ride(string $estado): void
    {
        $factura = $this->facturaAutorizada(['estado' => $estado]);

        $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/ride')->assertForbidden();
    }

    public static function estadosNoAutorizados(): array
    {
        return [
            'borrador' => [Factura::ESTADO_BORRADOR],
            'enviada' => [Factura::ESTADO_ENVIADA],
            'rechazada' => [Factura::ESTADO_RECHAZADA],
            'devuelta' => [Factura::ESTADO_DEVUELTA],
            'pendiente de anulacion' => [Factura::ESTADO_PENDIENTE_ANULAR],
        ];
    }

    #[Test]
    #[DataProvider('rolesDeOtraEmpresa')]
    public function ninguna_funcion_de_otra_empresa_puede_descargar_el_ride(string $rol, string $estado): void
    {
        $factura = $this->facturaAutorizada(['estado' => $estado]);
        $otro = User::factory()->for(Empresa::factory())->create();
        $otro->assignRole($rol);

        $this->actingAs($otro)->get('/facturas/'.$factura->id.'/ride')->assertForbidden();
    }

    public static function rolesDeOtraEmpresa(): array
    {
        return [
            'facturador autorizada' => ['facturador', Factura::ESTADO_AUTORIZADA],
            'facturador anulada' => ['facturador', Factura::ESTADO_ANULADA],
            'contador autorizada' => ['contador', Factura::ESTADO_AUTORIZADA],
            'contador anulada' => ['contador', Factura::ESTADO_ANULADA],
            'administrador autorizada' => ['admin', Factura::ESTADO_AUTORIZADA],
            'administrador anulada' => ['admin', Factura::ESTADO_ANULADA],
            'super administrador autorizada' => ['super_admin', Factura::ESTADO_AUTORIZADA],
            'super administrador anulada' => ['super_admin', Factura::ESTADO_ANULADA],
        ];
    }

    #[Test]
    public function un_usuario_sin_rol_no_puede_descargar_facturas_de_su_empresa(): void
    {
        $factura = $this->facturaAutorizada();
        $usuario = User::factory()->create(['empresa_id' => $this->usuario->empresa_id]);

        $this->actingAs($usuario)->get('/facturas/'.$factura->id.'/ride')->assertForbidden();
    }

    #[Test]
    public function un_contador_de_la_empresa_puede_descargar_la_factura_de_otro_usuario(): void
    {
        $factura = $this->facturaAutorizada();
        $contador = User::factory()->create(['empresa_id' => $this->usuario->empresa_id]);
        $contador->assignRole('contador');

        $this->actingAs($contador)->get('/facturas/'.$factura->id.'/ride')->assertOk();
    }

    #[Test]
    public function la_descarga_requiere_autenticacion(): void
    {
        $factura = $this->facturaAutorizada();

        $this->get('/facturas/'.$factura->id.'/ride')->assertRedirect(route('login'));
    }

    #[Test]
    public function el_boton_aparece_en_facturas_autorizadas_y_anuladas(): void
    {
        $factura = $this->facturaAutorizada();

        $this->actingAs($this->usuario)->get(route('facturas.show', $factura))
            ->assertOk()->assertSee('Descargar RIDE')
            ->assertSee(route('facturas.ride', $factura), false);

        $factura->update(['estado' => Factura::ESTADO_ANULADA]);

        $this->get(route('facturas.show', $factura))->assertOk()
            ->assertSee('Descargar RIDE')->assertSee(route('facturas.ride', $factura), false);

        $factura->update(['estado' => Factura::ESTADO_PENDIENTE_ANULAR]);

        $this->get(route('facturas.show', $factura))->assertOk()->assertDontSee('Descargar RIDE');
    }

    #[Test]
    public function agrupa_bases_e_iva_guardados_sin_recalcular_con_floats(): void
    {
        $factura = $this->facturaAutorizada([
            'total_sin_impuestos' => '15.30',
            'total_descuento' => '1.00',
            'importe_total' => '15.34',
            'forma_pago' => 19,
        ]);

        foreach ([
            ['codigo_porcentaje' => '4', 'tarifa' => '15.00', 'base_imponible' => '0.10', 'valor_impuesto' => '0.01'],
            ['codigo_porcentaje' => '4', 'tarifa' => '15.00', 'base_imponible' => '0.20', 'valor_impuesto' => '0.03'],
            ['codigo_porcentaje' => '0', 'tarifa' => '0.00', 'base_imponible' => '10.00', 'valor_impuesto' => '0.00'],
            ['codigo_porcentaje' => '6', 'tarifa' => '0.00', 'base_imponible' => '2.00', 'valor_impuesto' => '0.00'],
            ['codigo_porcentaje' => '7', 'tarifa' => '0.00', 'base_imponible' => '3.00', 'valor_impuesto' => '0.00'],
        ] as $valores) {
            FacturaDetalle::factory()->for($factura)->create($valores);
        }

        $datos = app(RideGenerator::class)->datos($factura);

        $this->assertSame('0.30', $datos['impuestos']['2|4']['base']);
        $this->assertSame('0.04', $datos['impuestos']['2|4']['valor']);
        $this->assertSame('IVA 15.00%', $datos['impuestos']['2|4']['etiqueta']);
        $this->assertSame('10.00', $datos['impuestos']['2|0']['base']);
        $this->assertSame('NO OBJETO DE IVA', $datos['impuestos']['2|6']['etiqueta']);
        $this->assertSame('EXENTO DE IVA', $datos['impuestos']['2|7']['etiqueta']);
        $this->assertSame('0.04', $datos['iva_total']);
        $this->assertSame('19', $datos['pagos'][0]['codigo']);
        $this->assertSame('Tarjeta de crédito', $datos['pagos'][0]['descripcion']);
        $this->assertSame('15.34', $datos['pagos'][0]['total']);

        $html = view('pdf.ride', $datos)->render();
        $this->assertStringContainsString('15.30', $html);
        $this->assertStringContainsString('1.00', $html);
        $this->assertStringContainsString('15.34', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
    }

    #[Test]
    public function conserva_los_datos_del_emisor_y_direccion_del_xml_autorizado(): void
    {
        $factura = $this->facturaAutorizada([
            'xml_autorizado' => '<factura><infoTributaria><ruc>1790012345001</ruc><razonSocial>EMISOR ORIGINAL</razonSocial><nombreComercial>COMERCIO ORIGINAL</nombreComercial><dirMatriz>MATRIZ ORIGINAL</dirMatriz><tipoEmision>1</tipoEmision></infoTributaria><infoFactura><dirEstablecimiento>LOCAL ORIGINAL</dirEstablecimiento><obligadoContabilidad>SI</obligadoContabilidad><contribuyenteEspecial>1234</contribuyenteEspecial></infoFactura></factura>',
        ]);
        $factura->empresa->update(['razon_social' => 'EMISOR MODIFICADO', 'dir_matriz' => 'MATRIZ MODIFICADA', 'ambiente' => 2]);

        $datos = app(RideGenerator::class)->datos($factura);
        $html = view('pdf.ride', $datos)->render();

        foreach (['1790012345001', 'EMISOR ORIGINAL', 'COMERCIO ORIGINAL', 'MATRIZ ORIGINAL', 'LOCAL ORIGINAL', '1234', 'PRUEBAS', 'NORMAL', '001-002-000000123', '04/10/2026 12:30:00', 'CLIENTE DE PRUEBA'] as $valor) {
            $this->assertStringContainsString($valor, $html);
        }
        $this->assertStringNotContainsString('EMISOR MODIFICADO', $html);
        $this->assertSame('SI', $datos['emisor']['obligado_contabilidad']);
    }

    #[Test]
    public function usa_el_xml_generado_y_la_matriz_cuando_no_hay_direccion_de_establecimiento(): void
    {
        $factura = $this->facturaAutorizada([
            'xml_generado' => '<factura><infoTributaria><razonSocial>EMISOR GUARDADO</razonSocial><dirMatriz>MATRIZ GUARDADA</dirMatriz></infoTributaria><infoFactura><obligadoContabilidad>NO</obligadoContabilidad></infoFactura></factura>',
        ]);

        $datos = app(RideGenerator::class)->datos($factura);
        $this->assertSame('EMISOR GUARDADO', $datos['emisor']['razon_social']);
        $this->assertSame('MATRIZ GUARDADA', $datos['emisor']['dir_establecimiento']);
        $this->assertSame('NO', $datos['emisor']['obligado_contabilidad']);
    }

    #[Test]
    public function una_clave_invalida_o_autorizacion_incompleta_no_produce_un_ride(): void
    {
        $factura = $this->facturaAutorizada(['clave_acceso' => '123']);

        $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/ride')->assertUnprocessable();

        $factura->update(['clave_acceso' => '0410202601179001234500110010020000001231234567810', 'fecha_autorizacion' => null]);

        $this->get('/facturas/'.$factura->id.'/ride')->assertUnprocessable();
    }

    #[Test]
    public function el_xml_con_entidades_externas_se_rechaza(): void
    {
        $factura = $this->facturaAutorizada([
            'xml_autorizado' => '<!DOCTYPE factura [<!ENTITY secreto SYSTEM "file:///etc/passwd">]><factura><infoTributaria><razonSocial>&secreto;</razonSocial></infoTributaria></factura>',
        ]);

        $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/ride')->assertUnprocessable();
    }

    #[Test]
    public function el_pdf_escapa_los_datos_del_comprador_y_del_detalle(): void
    {
        $factura = $this->facturaAutorizada(['razon_social_comprador' => '<script>alert(1)</script>']);
        FacturaDetalle::factory()->for($factura)->create(['descripcion' => '<img src="https://example.test/secreto">']);

        $html = view('pdf.ride', app(RideGenerator::class)->datos($factura))->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src="https://example.test/secreto">', $html);
    }

    private function facturaAutorizada(array $atributos = []): Factura
    {
        return Factura::factory()->autorizada()->create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'ambiente' => 1,
            'establecimiento' => '001',
            'punto_emision' => '002',
            'secuencial' => 123,
            'clave_acceso' => '0410202601179001234500110010020000001231234567810',
            'numero_autorizacion' => '0410202601179001234500110010020000001231234567810',
            'fecha_autorizacion' => '2026-10-04 12:30:00',
            'razon_social_comprador' => 'CLIENTE DE PRUEBA',
        ], $atributos));
    }
}
