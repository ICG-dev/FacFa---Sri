<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexErrorAlertTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function confirmar_sin_certificado_configurado_dispara_una_alerta_en_vez_de_reventar(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        
        
        $empresa = Empresa::factory()->create(['certificado_path' => null, 'certificado_password' => null]);
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        $factura = Factura::factory()->for($empresa)->create([
            'user_id' => $usuario->id,
            'estado' => Factura::ESTADO_BORRADOR,
        ]);
        FacturaDetalle::factory()->for($factura)->create();

        Livewire::actingAs($usuario)
            ->test(FacturaIndex::class)
            ->call('confirmar', $factura->id)
            ->assertDispatched('alerta-error');

        
        
        
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->fresh()->estado);
        $this->assertNull($factura->fresh()->clave_acceso);
    }
}
