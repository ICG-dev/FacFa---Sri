<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexResponsiveEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->for(Empresa::factory())->create();
        $this->usuario->assignRole('facturador');
        $this->actingAs($this->usuario);
    }

    #[Test]
    public function renderiza_estado_vacio_amigable_cuando_no_hay_facturas(): void
    {
        // Invoices from another company do not remove this company's empty state.
        Factura::factory()->create();

        Livewire::test(FacturaIndex::class)
            ->assertSee('Aún no tienes facturas registradas.')
            ->assertSee('Comienza emitiendo tu primera factura electrónica.')
            ->assertSee('Emitir primera factura')
            ->assertSeeHtml('href="'.route('facturas.create').'"');

        $this->get(route('facturas.index'))->assertOk()->assertSee('Nueva factura');
    }

    #[Test]
    public function estado_vacio_filtrado_ofrece_limpiar_filtros_y_recupera_facturas(): void
    {
        $factura = Factura::factory()->create([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'razon_social_comprador' => 'Cliente existente',
        ]);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', 'Cliente inexistente')
            ->assertSee('No se encontraron facturas con los filtros aplicados.')
            ->assertSeeHtml('wire:click="limpiarFiltros"')
            ->assertDontSee('Aún no tienes facturas registradas.')
            ->assertDontSee('Emitir primera factura')
            ->call('limpiarFiltros')
            ->assertSet('busqueda', '')
            ->assertSee($factura->razon_social_comprador)
            ->assertDontSee('No se encontraron facturas con los filtros aplicados.');
    }

    #[Test]
    public function pagina_vacia_no_afirma_que_la_empresa_carece_de_facturas(): void
    {
        $factura = Factura::factory()->create([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'razon_social_comprador' => 'Factura de primera página',
        ]);

        Livewire::test(FacturaIndex::class)
            ->call('setPage', 2)
            ->assertSee('No hay facturas en esta página.')
            ->assertSee('Volver a la primera página')
            ->assertSeeHtml('href="'.route('facturas.index').'"')
            ->assertDontSee('Aún no tienes facturas registradas.')
            ->assertDontSee('Emitir primera factura')
            ->call('setPage', 1)
            ->assertSee($factura->razon_social_comprador);
    }

    #[Test]
    public function estado_vacio_respeta_permiso_de_crear_facturas(): void
    {
        $this->usuario->syncRoles('contador');

        Livewire::test(FacturaIndex::class)
            ->assertSee('Aún no tienes facturas registradas.')
            ->assertDontSee('Emitir primera factura');

        $this->get(route('facturas.index'))->assertOk()->assertDontSee('Nueva factura');
    }

    #[Test]
    public function cabecera_y_tarjetas_adaptan_su_ancho_a_pantallas_estrechas(): void
    {
        $response = $this->get(route('facturas.index'))->assertOk();
        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($documento);

        $boton = $xpath->query('//main//a[@href="'.route('facturas.create').'"]')->item(0);
        $this->assertNotNull($boton);
        $this->assertStringContainsString('w-full sm:w-auto', $boton->getAttribute('class'));
        $this->assertSame(0, $xpath->query('//a//button')->length);

        $tarjetas = $xpath->query('//div[button[@aria-pressed]]')->item(0);
        $this->assertNotNull($tarjetas);
        $clases = $tarjetas->getAttribute('class');
        foreach (['grid-cols-2', 'sm:grid-cols-3', 'lg:grid-cols-5', 'gap-2', 'sm:gap-4'] as $clase) {
            $this->assertContains($clase, explode(' ', $clases));
        }
    }
}
