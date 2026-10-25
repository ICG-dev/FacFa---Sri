<?php

namespace Tests\Feature\Livewire\ReglasFacturacion;

use App\Jobs\ProcesarReglaFacturacionJob;
use App\Livewire\ReglasFacturacion\ReglaFacturacionIndex;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\Concerns\CreaReglaFacturacion;
use Tests\TestCase;

class ReglaFacturacionIndexTest extends TestCase
{
    use CreaReglaFacturacion, RefreshDatabase;

    public function test_paginacion_y_filtros_reinician_pagina(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $user = User::find($regla->user_id);
        $user->assignRole('admin');
        for ($i = 0; $i < 16; $i++) {
            $copia = $regla->replicate();
            $copia->nombre = 'Pausada '.$i;
            $copia->activo = false;
            $copia->save();
        }
        Livewire::actingAs($user)->test(ReglaFacturacionIndex::class)
            ->assertDontSee('Servicio mensual')->call('setPage', 2)->assertSee('Servicio mensual')
            ->set('estado', 'activa')->assertSee('Servicio mensual')->assertDontSee('Pausada 1');
    }

    public function test_lista_filtra_pausa_y_ejecuta_solo_su_empresa(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $ajena = $this->crearRegla(['nombre' => 'Regla ajena']);
        $user = User::find($regla->user_id);
        $user->assignRole('admin');
        Bus::fake();
        Livewire::actingAs($user)->test(ReglaFacturacionIndex::class)
            ->assertSee('Servicio mensual')->assertDontSee('Regla ajena')
            ->set('modo', 'automatica')->assertDontSee('Servicio mensual')
            ->set('modo', '')->call('ejecutar', $regla->id)
            ->call('toggleActivo', $regla->id);
        $this->assertFalse($regla->fresh()->activo);
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, 1);
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($user)->test(ReglaFacturacionIndex::class)->call('ejecutar', $ajena->id);
    }

    public function test_facturador_no_puede_ejecutar_ni_pausar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $user = User::find($regla->user_id);
        $user->assignRole('facturador');
        Livewire::actingAs($user)->test(ReglaFacturacionIndex::class)->call('ejecutar', $regla->id)->assertForbidden();
        Livewire::actingAs($user)->test(ReglaFacturacionIndex::class)->call('toggleActivo', $regla->id)->assertForbidden();
    }
}
