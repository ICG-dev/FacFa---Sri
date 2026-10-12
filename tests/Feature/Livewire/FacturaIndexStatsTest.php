<?php

namespace Tests\Feature\Livewire;

use App\Livewire\DashboardView;
use App\Livewire\Facturas\FacturaIndex;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexStatsTest extends TestCase
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

    #[Test]
    #[DataProvider('componentes')]
    public function agrupa_los_siete_estados_sin_mezclar_rechazadas_y_anuladas_y_solo_cuenta_su_empresa(string $componente): void
    {
        foreach (['borrador', 'enviada', 'autorizada', 'rechazada', 'devuelta', 'pendiente_anular', 'anulada'] as $estado) {
            $this->crearFactura($estado);
            Factura::factory()->create(['estado' => $estado]);
        }

        Livewire::test($componente)
            ->assertViewHas('stats', [
                'total' => 7,
                'autorizadas' => 1,
                'pendientes' => 2,
                'rechazadas' => 2,
                'anuladas' => 2,
            ])
            ->assertSee('Total')
            ->assertSee('Autorizadas')
            ->assertSee('Pendientes')
            ->assertSee('Rechazadas')
            ->assertSee('Anuladas');
    }

    #[Test]
    #[DataProvider('componentes')]
    public function una_rechazada_no_suma_como_anulada(string $componente): void
    {
        $this->crearFactura('rechazada');

        Livewire::test($componente)->assertViewHas('stats', [
            'total' => 1,
            'autorizadas' => 0,
            'pendientes' => 0,
            'rechazadas' => 1,
            'anuladas' => 0,
        ]);
    }

    public static function componentes(): array
    {
        return [[FacturaIndex::class], [DashboardView::class]];
    }

    #[Test]
    #[DataProvider('filtros')]
    public function filtra_por_grupo_o_estado_sin_cambiar_los_conteos(string $filtro, array $estados): void
    {
        foreach (['borrador', 'enviada', 'autorizada', 'rechazada', 'devuelta', 'pendiente_anular', 'anulada'] as $estado) {
            $this->crearFactura($estado);
            Factura::factory()->create(['estado' => $estado]);
        }

        Livewire::test(FacturaIndex::class)
            ->set('filtroEstado', $filtro)
            ->assertViewHas('facturas', function ($facturas) use ($estados) {
                $this->assertEqualsCanonicalizing($estados, $facturas->pluck('estado')->all());
                $this->assertTrue($facturas->every(fn ($factura) => $factura->empresa_id === $this->usuario->empresa_id));

                return true;
            })
            ->assertViewHas('stats', ['total' => 7, 'autorizadas' => 1, 'pendientes' => 2, 'rechazadas' => 2, 'anuladas' => 2])
            ->set('filtroEstado', '')
            ->assertViewHas('facturas', fn ($facturas) => $facturas->total() === 7);
    }

    public static function filtros(): array
    {
        return [
            ['autorizada', ['autorizada']],
            ['pendientes', ['borrador', 'enviada']],
            ['rechazadas', ['rechazada', 'devuelta']],
            ['anuladas', ['pendiente_anular', 'anulada']],
            ['devuelta', ['devuelta']],
            ['pendiente_anular', ['pendiente_anular']],
        ];
    }

    #[Test]
    public function cambiar_el_filtro_vuelve_a_la_primera_pagina(): void
    {
        Factura::factory()->count(16)->create([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
        ]);
        $autorizada = $this->crearFactura('autorizada');

        Livewire::test(FacturaIndex::class)
            ->call('setPage', 2)
            ->set('filtroEstado', 'autorizada')
            ->assertSet('paginators.page', 1)
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$autorizada->id]);
    }

    private function crearFactura(string $estado): Factura
    {
        return Factura::factory()->create([
            'empresa_id' => $this->usuario->empresa_id,
            'user_id' => $this->usuario->id,
            'estado' => $estado,
        ]);
    }
}
