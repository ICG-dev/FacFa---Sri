<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaShow;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaShowXmlModalTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private const XML = '<factura><razonSocial>Emisor &amp; Compañía</razonSocial></factura>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->for(Empresa::factory())->create();
        $this->usuario->assignRole('facturador');
    }

    #[Test]
    #[DataProvider('xmlDisponibles')]
    public function abre_el_modal_con_el_mejor_xml_disponible_y_lo_cierra(string $campo): void
    {
        $atributos = [$campo => self::XML];
        if ($campo !== 'xml_generado') {
            $atributos['xml_generado'] = '<factura>GENERADO</factura>';
        }
        if ($campo === 'xml_autorizado') {
            $atributos['xml_firmado'] = '<factura>FIRMADO</factura>';
        }
        $factura = $this->factura($atributos);

        Livewire::actingAs($this->usuario)->test(FacturaShow::class, ['factura' => $factura])
            ->assertSet('mostrarXmlModal', false)
            ->assertDontSee('XML del comprobante')
            ->call('verXmlModal')
            ->assertSet('mostrarXmlModal', true)
            ->assertSet('xmlContenido', self::XML)
            ->assertSee('XML del comprobante — Factura 001-002-000000123')
            ->assertSee($factura->clave_acceso)
            ->assertSeeHtml('role="dialog"')
            ->assertSeeHtml('aria-modal="true"')
            ->assertSeeHtml('&lt;razonSocial&gt;Emisor &amp;amp; Compañía&lt;/razonSocial&gt;')
            ->assertSee('Copiar XML')
            ->assertSee('Abrir en pestaña nueva')
            ->assertSeeHtml(route('facturas.xml', ['factura' => $factura, 'download' => 1]))
            ->call('cerrarXmlModal')
            ->assertSet('mostrarXmlModal', false)
            ->assertDontSee('XML del comprobante');
    }

    #[Test]
    public function rechaza_abrir_una_factura_sin_xml(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(FacturaShow::class, ['factura' => $this->factura()])
            ->call('verXmlModal')->assertNotFound();
    }

    #[Test]
    public function comprueba_el_permiso_al_abrir_el_modal(): void
    {
        $componente = Livewire::actingAs($this->usuario)
            ->test(FacturaShow::class, ['factura' => $this->factura(['xml_autorizado' => self::XML])]);
        $this->usuario->syncRoles([]);

        $componente->call('verXmlModal')->assertForbidden();
    }

    #[Test]
    public function otra_empresa_no_puede_ver_el_detalle_ni_su_xml(): void
    {
        $factura = $this->factura(['empresa_id' => Empresa::factory()->create()->id, 'xml_autorizado' => self::XML]);

        Livewire::actingAs($this->usuario)->test(FacturaShow::class, ['factura' => $factura])
            ->assertForbidden();
    }

    #[Test]
    public function el_xml_con_etiquetas_html_se_muestra_como_texto(): void
    {
        $xml = '<factura><script>alert("xss")</script></factura>';

        Livewire::actingAs($this->usuario)
            ->test(FacturaShow::class, ['factura' => $this->factura(['xml_autorizado' => $xml])])
            ->call('verXmlModal')
            ->assertSeeHtml('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
            ->assertDontSeeHtml('<script>alert("xss")</script>');
    }

    #[Test]
    public function el_xml_con_doctype_se_muestra_sin_expandir_entidades(): void
    {
        $xml = '<!DOCTYPE factura [<!ENTITY secreto "CONTENIDO_NO_EXPANDIR">]><factura>&secreto;</factura>';

        Livewire::actingAs($this->usuario)
            ->test(FacturaShow::class, ['factura' => $this->factura(['xml_autorizado' => $xml])])
            ->call('verXmlModal')
            ->assertSet('xmlContenido', $xml)
            ->assertSeeHtml('&lt;factura&gt;&amp;secreto;&lt;/factura&gt;');
    }

    public static function xmlDisponibles(): array
    {
        return [
            'autorizado' => ['xml_autorizado'],
            'firmado' => ['xml_firmado'],
            'generado' => ['xml_generado'],
        ];
    }

    private function factura(array $atributos = []): Factura
    {
        return Factura::factory()->autorizada()->create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'establecimiento' => '001',
            'punto_emision' => '002',
            'secuencial' => 123,
        ], $atributos));
    }
}
