<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexFechasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->create();
        $this->usuario->assignRole('facturador');
        $this->actingAs($this->usuario);
    }

    private function crearFactura(array $atributos = []): Factura
    {
        return Factura::factory()->create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
        ], $atributos));
    }

    #[Test]
    #[DataProvider('rangos')]
    public function filtra_facturas_por_rango_de_fechas(string $desde, string $hasta, array $fechas): void
    {
        foreach (['2026-08-01', '2026-08-15', '2026-08-31', '2026-09-01'] as $fecha) {
            $this->crearFactura(['fecha_emision' => $fecha]);
        }
        Factura::factory()->create(['fecha_emision' => '2026-08-15']);

        Livewire::test(FacturaIndex::class)
            ->set('fechaDesde', $desde)
            ->set('fechaHasta', $hasta)
            ->assertViewHas('facturas', function ($facturas) use ($fechas) {
                $this->assertEqualsCanonicalizing($fechas, $facturas->map(fn ($factura) => $factura->fecha_emision->toDateString())->all());

                return true;
            });
    }

    public static function rangos(): array
    {
        return [
            ['2026-08-15', '', ['2026-08-15', '2026-08-31', '2026-09-01']],
            ['', '2026-08-15', ['2026-08-01', '2026-08-15']],
            ['2026-08-01', '2026-08-31', ['2026-08-01', '2026-08-15', '2026-08-31']],
            ['2026-09-01', '2026-08-01', []],
        ];
    }

    #[Test]
    public function combina_busqueda_estado_y_fechas_sin_alterar_estadisticas(): void
    {
        $esperada = $this->crearFactura(['razon_social_comprador' => 'ACME', 'estado' => 'autorizada', 'fecha_emision' => '2026-08-15']);
        $this->crearFactura(['razon_social_comprador' => 'ACME', 'estado' => 'borrador', 'fecha_emision' => '2026-08-15']);
        $this->crearFactura(['razon_social_comprador' => 'ACME', 'estado' => 'autorizada', 'fecha_emision' => '2026-07-15']);
        $this->crearFactura(['razon_social_comprador' => 'Otro', 'estado' => 'autorizada', 'fecha_emision' => '2026-08-15']);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', 'ACME')
            ->set('filtroEstado', 'autorizada')
            ->set('fechaDesde', '2026-08-01')
            ->set('fechaHasta', '2026-08-31')
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$esperada->id])
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 4);
    }

    #[Test]
    public function limpiar_filtros_restaura_el_listado(): void
    {
        $this->crearFactura(['razon_social_comprador' => 'Factura A']);

        Livewire::test(FacturaIndex::class)
            ->assertDontSee('Limpiar filtros')
            ->set('busqueda', 'NoExiste')
            ->set('filtroEstado', 'autorizada')
            ->set('fechaDesde', '2026-08-01')
            ->set('fechaHasta', '2026-08-31')
            ->call('setPage', 2)
            ->assertSee('Limpiar filtros')
            ->assertDontSee('Factura A')
            ->call('limpiarFiltros')
            ->assertSet('busqueda', '')
            ->assertSet('filtroEstado', '')
            ->assertSet('fechaDesde', '')
            ->assertSet('fechaHasta', '')
            ->assertSet('paginators.page', 1)
            ->assertSee('Factura A')
            ->assertDontSee('Limpiar filtros');
    }

    #[Test]
    #[DataProvider('filtrosActivos')]
    public function cada_filtro_activo_permite_limpiar(string $propiedad, string $valor): void
    {
        Livewire::test(FacturaIndex::class)
            ->assertDontSee('Limpiar filtros')
            ->set($propiedad, $valor)
            ->assertSee('Limpiar filtros')
            ->call('limpiarFiltros')
            ->assertDontSee('Limpiar filtros');
    }

    public static function filtrosActivos(): array
    {
        return [['busqueda', '0'], ['filtroEstado', 'autorizada'], ['fechaDesde', '2026-08-01'], ['fechaHasta', '2026-08-31']];
    }

    #[Test]
    #[DataProvider('fechasReactivas')]
    public function cambiar_fecha_vuelve_a_la_primera_pagina(string $propiedad): void
    {
        Factura::factory()->count(16)->create(['empresa_id' => $this->usuario->empresa_id, 'fecha_emision' => '2026-08-15']);

        Livewire::test(FacturaIndex::class)
            ->call('setPage', 2)
            ->set($propiedad, '2026-08-15')
            ->assertSet('paginators.page', 1)
            ->assertViewHas('facturas', fn ($facturas) => $facturas->total() === 16);
    }

    public static function fechasReactivas(): array
    {
        return [['fechaDesde'], ['fechaHasta']];
    }
}
