<?php

namespace Tests\Feature\Sri;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class XmlDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private const XML = '<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante"><infoTributaria><razonSocial>EMISOR DE PRUEBA</razonSocial></infoTributaria></factura>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->for(Empresa::factory())->create();
        $this->usuario->assignRole('facturador');
    }

    #[Test]
    public function descarga_el_xml_autorizado_original_con_headers_privados(): void
    {
        $factura = $this->factura([
            'xml_autorizado' => self::XML,
            'xml_firmado' => '<factura>FIRMADO</factura>',
            'xml_generado' => '<factura>GENERADO</factura>',
        ]);

        $response = $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/xml?download=1');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(self::XML, $response->getContent());
        $this->assertSame('attachment; filename="FACTURA-001-002-000000123.xml"', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[Test]
    #[DataProvider('xmlDisponibles')]
    public function permite_ver_xml_guardado_y_prioriza_firmado_sobre_generado(string $estado, string $campo): void
    {
        $atributos = ['estado' => $estado, $campo => self::XML];
        if ($campo === 'xml_firmado') {
            $atributos['xml_generado'] = '<factura>GENERADO</factura>';
        }
        $factura = $this->factura($atributos);

        $response = $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/xml');
        $response->assertOk();
        $this->assertSame(self::XML, $response->getContent());
        $this->assertSame('inline; filename="FACTURA-001-002-000000123.xml"', $response->headers->get('Content-Disposition'));
    }

    public static function xmlDisponibles(): array
    {
        return [
            'autorizada' => [Factura::ESTADO_AUTORIZADA, 'xml_autorizado'],
            'anulada' => [Factura::ESTADO_ANULADA, 'xml_autorizado'],
            'devuelta con firma' => [Factura::ESTADO_DEVUELTA, 'xml_firmado'],
            'borrador generado' => [Factura::ESTADO_BORRADOR, 'xml_generado'],
        ];
    }

    #[Test]
    #[DataProvider('roles')]
    public function otra_empresa_no_puede_acceder_al_xml(string $rol): void
    {
        $factura = $this->factura(['xml_autorizado' => self::XML]);
        $otro = User::factory()->for(Empresa::factory())->create();
        $otro->assignRole($rol);

        $this->actingAs($otro)->get('/facturas/'.$factura->id.'/xml')->assertForbidden();
        $this->get('/facturas/'.$factura->id.'/xml?download=1')->assertForbidden();
    }

    public static function roles(): array
    {
        return [['facturador'], ['contador'], ['admin'], ['super_admin']];
    }

    #[Test]
    public function un_usuario_sin_rol_no_puede_acceder_al_xml(): void
    {
        $factura = $this->factura(['xml_autorizado' => self::XML]);
        $otro = User::factory()->create(['empresa_id' => $this->usuario->empresa_id]);

        $this->actingAs($otro)->get('/facturas/'.$factura->id.'/xml')->assertForbidden();
    }

    #[Test]
    public function requiere_login(): void
    {
        $factura = $this->factura(['xml_autorizado' => self::XML]);
        $this->get('/facturas/'.$factura->id.'/xml')->assertRedirect(route('login'));
    }

    #[Test]
    public function una_factura_autorizada_sin_xml_responde_no_encontrado(): void
    {
        $factura = $this->factura();
        $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/xml')->assertNotFound();
    }

    #[Test]
    public function un_borrador_sin_xml_no_permite_acceso(): void
    {
        $factura = $this->factura(['estado' => Factura::ESTADO_BORRADOR]);
        $this->actingAs($this->usuario)->get('/facturas/'.$factura->id.'/xml')->assertForbidden();
    }

    #[Test]
    public function muestra_los_enlaces_en_la_vista_y_el_listado(): void
    {
        $factura = $this->factura(['xml_autorizado' => self::XML]);

        foreach ([Factura::ESTADO_AUTORIZADA, Factura::ESTADO_ANULADA] as $estado) {
            $factura->update(['estado' => $estado]);
            $ver = url('/facturas/'.$factura->id.'/xml');
            $descargar = $ver.'?download=1';

            $this->actingAs($this->usuario)->get(route('facturas.show', $factura))->assertOk()
                ->assertSee('Ver XML')->assertSee('Descargar XML')
                ->assertSee($ver, false)->assertSee($descargar, false);
            $this->get(route('facturas.index'))->assertOk()
                ->assertSee(url('/facturas/'.$factura->id.'/ride'), false)
                ->assertSee($ver, false)->assertSee($descargar, false);
        }
    }

    #[Test]
    public function oculta_los_enlaces_xml_cuando_no_hay_contenido(): void
    {
        $factura = $this->factura();
        $this->actingAs($this->usuario)->get(route('facturas.show', $factura))->assertOk()
            ->assertDontSee('Ver XML')->assertDontSee('Descargar XML');
        $this->get(route('facturas.index'))->assertOk()
            ->assertDontSee(url('/facturas/'.$factura->id.'/xml'), false);
    }

    private function factura(array $atributos = []): Factura
    {
        return Factura::factory()->autorizada()->create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'ambiente' => 1,
            'establecimiento' => '001',
            'punto_emision' => '002',
            'secuencial' => 123,
        ], $atributos));
    }
}
