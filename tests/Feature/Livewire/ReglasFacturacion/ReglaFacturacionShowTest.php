<?php

namespace Tests\Feature\Livewire\ReglasFacturacion;

use App\Actions\ReglasFacturacion\EjecutarReglaFacturacionAction;
use App\Jobs\ProcesarReglaFacturacionJob;
use App\Livewire\Productos\ProductoForm;
use App\Livewire\Productos\ProductoIndex;
use App\Livewire\ReglasFacturacion\ReglaFacturacionShow;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\Concerns\CreaReglaFacturacion;
use Tests\TestCase;

class ReglaFacturacionShowTest extends TestCase
{
    use CreaReglaFacturacion, RefreshDatabase;

    public function test_historial_reintento_y_badges_productos(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(8, 0));
        $this->seed(RolesAndPermissionsSeeder::class);
        $regla = $this->crearRegla();
        $user = User::find($regla->user_id);
        $user->assignRole('admin');
        $regla->clientes()->first()->update(['direccion' => null]);
        app(EjecutarReglaFacturacionAction::class)->ejecutar($regla);
        $ejecucion = $regla->ejecuciones()->sole();
        Bus::fake();
        Livewire::actingAs($user)->test(ReglaFacturacionShow::class, ['regla' => $regla])
            ->assertSee('2026-10')->assertSee('fallido')->call('reintentar', $ejecucion->id);
        Bus::assertDispatched(ProcesarReglaFacturacionJob::class, fn ($job) => $job->clienteIdentificacion === 'ABC123' && $job->periodoReintento === '2026-10');
        Livewire::actingAs($user)->test(ProductoIndex::class)->assertSee('1 regla');
        Livewire::actingAs($user)->test(ProductoForm::class, ['producto' => $regla->productos()->first()->producto])->assertSee('reglas periódicas');
        $ajena = $this->crearRegla();
        Livewire::actingAs($user)->test(ReglaFacturacionShow::class, ['regla' => $ajena])->assertForbidden();
    }
}
