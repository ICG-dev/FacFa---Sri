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

class FacturaIndexPaginacionTest extends TestCase
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
    public function muestra_resumen_contable_de_registros(): void
    {
        Factura::factory()->count(3)->create(['empresa_id' => $this->usuario->empresa_id]);
        Factura::factory()->create();

        Livewire::test(FacturaIndex::class)->assertSee('Mostrando 1 a 3 de 3 facturas');
    }

    #[Test]
    public function resumen_refleja_pagina_actual_y_total_filtrado(): void
    {
        Factura::factory()->count(16)->create(['empresa_id' => $this->usuario->empresa_id, 'estado' => 'autorizada']);
        $this->crearFactura(['estado' => 'borrador']);

        Livewire::test(FacturaIndex::class)
            ->set('filtroEstado', 'autorizada')
            ->assertSee('Mostrando 1 a 15 de 16 facturas')
            ->call('setPage', 2)
            ->assertSee('Mostrando 16 a 16 de 16 facturas');
    }

    #[Test]
    public function muestra_resumen_cuando_no_hay_facturas(): void
    {
        Livewire::test(FacturaIndex::class)->assertSee('Mostrando 0 a 0 de 0 facturas');
    }
}
