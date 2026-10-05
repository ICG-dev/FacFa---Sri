<?php

namespace Tests\Unit;

use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaFechaEmisionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_formulario_usa_la_fecha_de_ecuador_al_anochecer(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        Carbon::setTestNow('2026-10-04 02:00:00 UTC');

        try {
            $this->assertSame('America/Guayaquil', now()->timezoneName);
            Livewire::actingAs($usuario)->test(FacturaForm::class)
                ->assertSet('fecha_emision', '2026-10-03');
        } finally {
            Carbon::setTestNow();
        }
    }
}
