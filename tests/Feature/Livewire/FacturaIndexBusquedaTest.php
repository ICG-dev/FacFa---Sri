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

class FacturaIndexBusquedaTest extends TestCase
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
    #[DataProvider('terminosCliente')]
    public function busca_por_razon_social_del_comprador(string $termino): void
    {
        $acme = $this->crearFactura(['razon_social_comprador' => 'ACME Comercial']);
        $this->crearFactura(['razon_social_comprador' => 'Otro cliente']);
        Factura::factory()->create(['razon_social_comprador' => 'ACME de otra empresa']);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', $termino)
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$acme->id]);
    }

    public static function terminosCliente(): array
    {
        return [['ACME'], ['acme'], ['  AcMe  ']];
    }

    #[Test]
    #[DataProvider('secuenciales')]
    public function busca_por_secuencial_numerico_o_formateado(string $termino): void
    {
        $factura = $this->crearFactura(['secuencial' => 888, 'establecimiento' => '001', 'punto_emision' => '001']);
        $this->crearFactura(['secuencial' => 889, 'establecimiento' => '001', 'punto_emision' => '001']);
        Factura::factory()->create(['secuencial' => 888]);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', $termino)
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$factura->id]);
    }

    public static function secuenciales(): array
    {
        return [['888'], ['000000888'], ['001-001-000000888']];
    }

    #[Test]
    #[DataProvider('identificacionesYClaves')]
    public function busca_por_identificacion_o_clave(string $termino): void
    {
        $factura = $this->crearFactura([
            'identificacion_comprador' => '0191234567001',
            'clave_acceso' => '1111111111111111111111111111111111111111111111111',
        ]);
        $this->crearFactura(['identificacion_comprador' => '0997654321001']);
        Factura::factory()->create(['identificacion_comprador' => '0191234567001']);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', $termino)
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$factura->id]);
    }

    public static function identificacionesYClaves(): array
    {
        return [['0191234567001'], ['1234567'], ['1111111111111111111111111111111111111111111111111']];
    }

    #[Test]
    public function cambiar_busqueda_vuelve_a_la_primera_pagina(): void
    {
        $this->crearFactura(['razon_social_comprador' => 'ACME']);
        Factura::factory()->count(15)->create(['empresa_id' => $this->usuario->empresa_id]);

        Livewire::test(FacturaIndex::class)
            ->call('setPage', 2)
            ->set('busqueda', 'ACME')
            ->assertSet('paginators.page', 1)
            ->assertSee('ACME')
            ->assertViewHas('facturas', fn ($facturas) => $facturas->total() === 1);
    }

    #[Test]
    public function busqueda_cero_no_se_ignora(): void
    {
        $factura = $this->crearFactura(['razon_social_comprador' => 'Cliente 0', 'identificacion_comprador' => '1111111111']);
        $this->crearFactura(['razon_social_comprador' => 'Otro', 'identificacion_comprador' => '2222222222']);

        Livewire::test(FacturaIndex::class)
            ->set('busqueda', '0')
            ->assertViewHas('facturas', fn ($facturas) => $facturas->pluck('id')->all() === [$factura->id]);
    }
}
