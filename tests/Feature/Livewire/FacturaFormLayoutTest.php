<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaFormLayoutTest extends TestCase
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
    public function renderiza_estructura_con_sticky_y_toggle_mas_opciones(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->assertSeeHtml('lg:grid-cols-3')
            ->assertSee('Comprobante y comprador')
            ->assertSee('Ítems de la factura')
            ->assertSeeHtml('sticky top-6')
            ->assertSeeHtml('masOpciones')
            ->assertSee('Más opciones');
    }

    #[Test]
    public function cada_item_tiene_su_panel_independiente_de_campos_secundarios(): void
    {
        $form = Livewire::actingAs($this->usuario)->test(FacturaForm::class)->call('agregarLinea')
            ->assertSeeHtml('@click="masOpciones = !masOpciones"');
        $xpath = $this->xpath($form->html());

        $paneles = $xpath->query('//*[@x-show="masOpciones"]');
        $this->assertCount(2, $paneles);

        foreach ($paneles as $indice => $panel) {
            $this->assertNotNull($panel->parentNode->attributes->getNamedItem('x-data'));
            foreach (['codigo_auxiliar', 'detalle_adicional', 'subsidio'] as $campo) {
                $this->assertCount(1, $xpath->query('.//input[@*[starts-with(name(), "wire:model")]="lineas.'.$indice.'.'.$campo.'"]', $panel));
            }
            $this->assertStringContainsString('P. sin subsidio', $panel->textContent);
            $this->assertCount(0, $xpath->query('.//input[@*[starts-with(name(), "wire:model")]="lineas.'.$indice.'.precio_unitario"]', $panel));
            $this->assertCount(1, $xpath->query('.//button[@type="button" and @aria-controls="opciones-'.$indice.'"]', $panel->parentNode));
        }
    }

    #[Test]
    public function los_campos_del_comprador_activan_autocompletado_sin_enviar_el_formulario(): void
    {
        $form = Livewire::actingAs($this->usuario)->test(FacturaForm::class);
        $xpath = $this->xpath($form->html());

        foreach (['tipo_identificacion_comprador', 'identificacion_comprador'] as $campo) {
            $this->assertCount(1, $xpath->query('//*[@id="'.$campo.'"]/@*[starts-with(name(), "wire:model.live")]'));
        }
    }

    #[Test]
    public function el_panel_lateral_contiene_totales_reactivos_y_acciones_del_formulario(): void
    {
        $form = Livewire::actingAs($this->usuario)->test(FacturaForm::class)
            ->set('lineas.0.cantidad', '2')
            ->set('lineas.0.precio_unitario', '50')
            ->set('lineas.0.descuento', '10')
            ->set('lineas.0.codigo_porcentaje', '4');
        $xpath = $this->xpath($form->html());
        $panel = $xpath->query('//form//aside//*[contains(@class, "sticky top-6")]')->item(0);

        $this->assertNotNull($panel);
        foreach (['Subtotal', '$90.00', 'Descuento', '$10.00', 'IVA', '$13.50', 'Total', '$103.50', 'Guardar borrador', 'Cancelar'] as $texto) {
            $this->assertStringContainsString($texto, $panel->textContent);
        }
        $this->assertCount(1, $xpath->query('.//button[@type="submit"]', $panel));
        $this->assertCount(1, $xpath->query('.//a[@href="'.route('facturas.index').'"]', $panel));
    }

    #[Test]
    public function revela_opciones_secundarias_cuando_tienen_errores_de_validacion(): void
    {
        Livewire::actingAs($this->usuario)->test(FacturaForm::class)
            ->set('lineas.0.subsidio', '1')
            ->call('guardar')
            ->assertHasErrors(['lineas.0.subsidio'])
            ->assertSeeHtml('x-effect="if (true) masOpciones = true"');
    }

    private function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        $anterior = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return new DOMXPath($documento);
    }
}
