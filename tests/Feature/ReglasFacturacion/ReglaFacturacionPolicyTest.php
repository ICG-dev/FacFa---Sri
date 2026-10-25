<?php

namespace Tests\Feature\ReglasFacturacion;

use App\Models\Empresa;
use App\Models\ReglaFacturacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReglaFacturacionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_y_aislamiento(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $regla = new ReglaFacturacion(['empresa_id' => $empresa->id]);
        foreach (['admin', 'contador', 'facturador'] as $rol) {
            $user = User::factory()->for($empresa)->create();
            $user->assignRole($rol);
            $gate = Gate::forUser($user);
            $this->assertTrue($gate->allows('viewAny', ReglaFacturacion::class));
            $this->assertTrue($gate->allows('view', $regla));
            $this->assertSame($rol !== 'facturador', $gate->allows('create', ReglaFacturacion::class));
            foreach (['update', 'ejecutar'] as $ability) {
                $this->assertSame($rol !== 'facturador', $gate->allows($ability, $regla));
            }
            $this->assertSame($rol === 'admin', $gate->allows('delete', $regla));
            $ajena = new ReglaFacturacion(['empresa_id' => Empresa::factory()->create()->id]);
            foreach (['view', 'update', 'delete', 'ejecutar'] as $ability) {
                $this->assertFalse($gate->allows($ability, $ajena));
            }
        }
    }
}
