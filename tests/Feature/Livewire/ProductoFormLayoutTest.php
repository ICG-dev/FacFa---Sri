<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Productos\ProductoForm;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductoFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->for(Empresa::factory())->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function renderiza_placeholders_y_ayudas_visibles(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ProductoForm::class)
            ->assertSeeHtml('placeholder="PROD-001"')
            ->assertSeeHtml('placeholder="Ej. Servicio de consultoría informática o Laptop ThinkPad..."')
            ->assertSeeHtml('placeholder="0.00"')
            ->assertSee('Código interno o identificador único del producto (máx. 25 caracteres).')
            ->assertSee('Detalle comercial que aparecerá impreso en la factura.')
            ->assertSee('Precio unitario antes de impuestos (hasta 6 decimales).')
            ->assertSee('Selecciona la tarifa vigente del catálogo del SRI.');
    }

    #[Test]
    public function formulario_amplio_agrupa_precio_e_iva_en_un_grid_responsive(): void
    {
        $componente = Livewire::actingAs($this->admin)->test(ProductoForm::class);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<meta charset="UTF-8">'.$componente->html());
        $xpath = new \DOMXPath($dom);

        $this->assertSame(1, $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " max-w-3xl ")]/form')->length);
        $grid = $xpath->query('//input[@id="precio_unitario"]/ancestor::*[contains(concat(" ", normalize-space(@class), " "), " grid ")][1]')->item(0);
        $this->assertNotNull($grid);
        $this->assertStringContainsString('grid-cols-1', $grid->getAttribute('class'));
        $this->assertStringContainsString('sm:grid-cols-2', $grid->getAttribute('class'));
        $this->assertSame(1, $xpath->query('.//select[@id="codigo_porcentaje"]', $grid)->length);
    }
}
