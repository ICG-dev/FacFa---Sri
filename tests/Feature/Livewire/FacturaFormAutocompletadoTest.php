<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaForm;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaFormAutocompletadoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->empresa = Empresa::factory()->create();
        $this->usuario = User::factory()->for($this->empresa)->create();
        $this->usuario->assignRole('facturador');
    }

    #[Test]
    public function autocompleta_consumidor_final_al_seleccionar_tipo_07(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('tipo_identificacion_comprador', '07')
            ->assertSet('identificacion_comprador', '9999999999999')
            ->assertSet('razon_social_comprador', 'CONSUMIDOR FINAL');
    }

    #[Test]
    public function autocompleta_datos_del_cliente_desde_factura_historica(): void
    {
        $this->crearHistorica();

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('tipo_identificacion_comprador', '04')
            ->set('identificacion_comprador', '1792146739001')
            ->assertSet('razon_social_comprador', 'SERVICIOS TECNOLOGICOS S.A.')
            ->assertSet('direccion_comprador', 'Av. Amazonas N24-196')
            ->assertSet('telefono_comprador', '0991234567')
            ->assertSet('correo_comprador', 'facturacion@servicios.ec');
    }

    #[Test]
    public function usa_la_factura_mas_reciente_de_la_misma_empresa(): void
    {
        $this->crearHistorica(['razon_social_comprador' => 'Cliente anterior']);
        $this->crearHistorica(['razon_social_comprador' => 'Cliente actualizado']);
        $this->crearHistorica([
            'empresa_id' => Empresa::factory()->create()->id,
            'razon_social_comprador' => 'Cliente de otra empresa',
            'correo_comprador' => 'privado@otra.ec',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('identificacion_comprador', '1792146739001')
            ->assertSet('razon_social_comprador', 'Cliente actualizado')
            ->assertSet('correo_comprador', 'facturacion@servicios.ec');
    }

    #[Test]
    public function conserva_datos_manuales_y_solo_rellena_campos_vacios(): void
    {
        $this->crearHistorica();

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Nombre manual')
            ->set('direccion_comprador', 'Dirección manual')
            ->set('correo_comprador', 'manual@example.com')
            ->set('identificacion_comprador', '1792146739001')
            ->assertSet('razon_social_comprador', 'Nombre manual')
            ->assertSet('direccion_comprador', 'Dirección manual')
            ->assertSet('correo_comprador', 'manual@example.com')
            ->assertSet('telefono_comprador', '0991234567');
    }

    #[Test]
    public function reemplaza_nombre_generico_de_consumidor_final_al_identificar_cliente(): void
    {
        $this->crearHistorica();

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('tipo_identificacion_comprador', '07')
            ->set('tipo_identificacion_comprador', '04')
            ->set('identificacion_comprador', ' 1792146739001 ')
            ->assertSet('razon_social_comprador', 'SERVICIOS TECNOLOGICOS S.A.');
    }

    #[Test]
    public function convierte_contactos_historicos_nulos_en_campos_vacios(): void
    {
        $this->crearHistorica([
            'direccion_comprador' => null,
            'telefono_comprador' => null,
            'correo_comprador' => null,
        ]);

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('identificacion_comprador', '1792146739001')
            ->assertSet('razon_social_comprador', 'SERVICIOS TECNOLOGICOS S.A.')
            ->assertSet('direccion_comprador', '')
            ->assertSet('telefono_comprador', '')
            ->assertSet('correo_comprador', '');
    }

    #[Test]
    public function no_autocompleta_identificaciones_cortas_o_sin_historial_propio(): void
    {
        $this->crearHistorica(['identificacion_comprador' => '123456789']);
        $this->crearHistorica(['empresa_id' => Empresa::factory()->create()->id]);

        Livewire::actingAs($this->usuario)
            ->test(FacturaForm::class)
            ->set('identificacion_comprador', '123456789')
            ->assertSet('razon_social_comprador', '')
            ->set('identificacion_comprador', '1792146739001')
            ->assertSet('razon_social_comprador', '')
            ->assertSet('correo_comprador', '')
            ->set('identificacion_comprador', '9998887776')
            ->assertSet('razon_social_comprador', '');
    }

    private function crearHistorica(array $datos = []): Factura
    {
        return Factura::factory()->create(array_merge([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->usuario->id,
            'identificacion_comprador' => '1792146739001',
            'razon_social_comprador' => 'SERVICIOS TECNOLOGICOS S.A.',
            'direccion_comprador' => 'Av. Amazonas N24-196',
            'telefono_comprador' => '0991234567',
            'correo_comprador' => 'facturacion@servicios.ec',
        ], $datos));
    }
}
