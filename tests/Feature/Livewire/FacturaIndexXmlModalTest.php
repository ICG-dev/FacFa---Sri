<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexXmlModalTest extends TestCase
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
    public function abre_el_modal_con_el_mejor_xml_disponible_y_limpia_al_cerrar(string $campo): void
    {
        $atributos = [$campo => self::XML];
        if ($campo !== 'xml_generado') {
            $atributos['xml_generado'] = '<factura>GENERADO</factura>';
        }
        if ($campo === 'xml_autorizado') {
            $atributos['xml_firmado'] = '<factura>FIRMADO</factura>';
        }
        $factura = $this->factura($atributos);

        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->assertSet('facturaXmlId', null)
            ->call('verXmlModal', $factura->id)
            ->assertSet('facturaXmlId', $factura->id)
            ->assertSet('xmlModalContenido', self::XML)
            ->assertSet('xmlModalNumero', '001-002-000000123')
            ->assertSee('XML del comprobante — Factura 001-002-000000123')
            ->assertSee($factura->clave_acceso)
            ->assertSeeHtml('&lt;razonSocial&gt;Emisor &amp;amp; Compañía&lt;/razonSocial&gt;')
            ->call('cerrarXmlModal')
            ->assertSet('facturaXmlId', null)
            ->assertSet('xmlModalContenido', null)
            ->assertSet('xmlModalNumero', null)
            ->assertDontSee('XML del comprobante');
    }

    #[Test]
    public function no_permite_cargar_xml_de_otra_empresa(): void
    {
        $factura = $this->factura(['empresa_id' => Empresa::factory()->create()->id, 'xml_autorizado' => self::XML]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->call('verXmlModal', $factura->id);
    }

    #[Test]
    public function comprueba_el_permiso_al_abrir_el_modal(): void
    {
        $factura = $this->factura(['xml_autorizado' => self::XML]);
        $componente = Livewire::actingAs($this->usuario)->test(FacturaIndex::class);
        $this->usuario->syncRoles([]);

        $componente->call('verXmlModal', $factura->id)->assertForbidden();
    }

    #[Test]
    public function una_factura_sin_xml_no_abre_un_modal_vacio(): void
    {
        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->call('verXmlModal', $this->factura()->id)->assertNotFound();
    }

    #[Test]
    public function el_menu_incluye_acciones_de_borrador_con_sus_confirmaciones(): void
    {
        $factura = $this->factura(['estado' => Factura::ESTADO_BORRADOR, 'xml_generado' => self::XML]);

        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->assertSee('Menú de acciones')
            ->assertSee('Ver detalle')
            ->assertSee('Ver XML')
            ->assertSee('Descargar XML')
            ->assertSee('Editar borrador')
            ->assertSee('Confirmar y enviar al SRI')
            ->assertSee('Descartar borrador')
            ->assertSeeHtml('wire:click="verXmlModal('.$factura->id.')"')
            ->assertSeeHtml('wire:confirm=')
            ->assertDontSee('Descargar RIDE');
    }

    #[Test]
    public function el_menu_oculta_acciones_de_escritura_para_un_contador(): void
    {
        $this->usuario->syncRoles(['contador']);
        $this->factura(['xml_autorizado' => self::XML]);

        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->assertSee('Ver detalle')->assertSee('Descargar RIDE')->assertSee('Ver XML')
            ->assertDontSee('Editar borrador')->assertDontSee('Confirmar y enviar al SRI')
            ->assertDontSee('Descartar borrador');
    }

    #[Test]
    public function permite_cambiar_de_factura_sin_conservar_el_xml_anterior(): void
    {
        $primera = $this->factura(['xml_autorizado' => self::XML]);
        $segunda = $this->factura(['xml_generado' => '<factura>SEGUNDA</factura>', 'secuencial' => 456]);

        Livewire::actingAs($this->usuario)->test(FacturaIndex::class)
            ->call('verXmlModal', $primera->id)
            ->call('verXmlModal', $segunda->id)
            ->assertSet('facturaXmlId', $segunda->id)
            ->assertSet('xmlModalContenido', '<factura>SEGUNDA</factura>')
            ->assertSet('xmlModalNumero', '001-002-000000456');
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
