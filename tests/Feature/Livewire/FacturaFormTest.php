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

class FacturaFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_facturador_puede_crear_una_factura_en_borrador_con_totales_calculados(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        Livewire::actingAs($usuario)
            ->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Juan Pérez')
            ->set('identificacion_comprador', '0100000009')
            ->set('direccion_comprador', 'Av. Siempre Viva 123')
            ->set('correo_comprador', 'juan.perez@example.com')
            ->set('lineas.0.codigo_principal', 'PROD-1')
            ->set('lineas.0.descripcion', 'Servicio de prueba')
            ->set('lineas.0.cantidad', 2)
            ->set('lineas.0.precio_unitario', 50)
            ->set('lineas.0.descuento', 0)
            ->set('lineas.0.codigo_porcentaje', '4')
            ->call('guardar')
            ->assertHasNoErrors();

        $factura = Factura::first();

        $this->assertNotNull($factura);
        $this->assertSame($empresa->id, $factura->empresa_id);
        $this->assertSame($usuario->id, $factura->user_id);
        $this->assertSame(Factura::ESTADO_BORRADOR, $factura->estado);
        $this->assertSame('juan.perez@example.com', $factura->correo_comprador);
        $this->assertSame('100.00', (string) $factura->total_sin_impuestos);
        $this->assertSame('115.00', (string) $factura->importe_total);
        $this->assertCount(1, $factura->detalles);
    }

    #[Test]
    public function no_se_puede_guardar_sin_correo_electronico_del_comprador(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        Livewire::actingAs($usuario)
            ->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Juan Pérez')
            ->set('identificacion_comprador', '0100000009')
            ->set('lineas.0.codigo_principal', 'PROD-1')
            ->set('lineas.0.descripcion', 'Servicio de prueba')
            ->set('lineas.0.cantidad', 1)
            ->set('lineas.0.precio_unitario', 50)
            ->call('guardar')
            ->assertHasErrors(['correo_comprador' => 'required']);
    }

    #[Test]
    public function el_subsidio_de_linea_reduce_el_precio_sin_subsidio_y_el_total(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('facturador');

        Livewire::actingAs($usuario)
            ->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Juan Pérez')
            ->set('identificacion_comprador', '0100000009')
            ->set('correo_comprador', 'juan.perez@example.com')
            ->set('lineas.0.codigo_principal', 'PROD-1')
            ->set('lineas.0.descripcion', 'Servicio de prueba')
            ->set('lineas.0.cantidad', 2)
            ->set('lineas.0.precio_unitario', 50)
            ->set('lineas.0.subsidio', 10)
            ->set('lineas.0.descuento', 0)
            ->set('lineas.0.codigo_porcentaje', '0')
            ->call('guardar')
            ->assertHasNoErrors();

        $factura = Factura::first();
        $detalle = $factura->detalles->first();

        $this->assertSame('40.000000', (string) $detalle->precio_sin_subsidio);
        $this->assertSame('80.00', (string) $detalle->precio_total_sin_impuesto);
    }

    #[Test]
    public function un_contador_no_puede_crear_facturas(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->create(['empresa_id' => $empresa->id]);
        $usuario->assignRole('contador');

        $this->actingAs($usuario)->get(route('facturas.create'))->assertForbidden();
    }

    #[Test]
    public function rechaza_lineas_y_catalogos_invalidos_sin_guardar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Comprador')
            ->set('correo_comprador', 'comprador@example.com')
            ->set('tipo_identificacion_comprador', '07')
            ->set('identificacion_comprador', '123')
            ->set('forma_pago', 999)
            ->set('lineas.0.codigo_principal', 'A')
            ->set('lineas.0.descripcion', 'Producto')
            ->set('lineas.0.precio_unitario', 10)
            ->set('lineas.0.subsidio', 11)
            ->set('lineas.0.descuento', 20)
            ->set('lineas.0.codigo_porcentaje', '2')
            ->call('guardar')
            ->assertHasErrors(['lineas.0.subsidio', 'lineas.0.descuento', 'lineas.0.codigo_porcentaje', 'identificacion_comprador', 'forma_pago']);

        $form->set('tipo_identificacion_comprador', '99')->call('guardar')
            ->assertHasErrors(['tipo_identificacion_comprador']);
        $form->set('tipo_identificacion_comprador', '05')->call('guardar')
            ->assertHasErrors(['identificacion_comprador']);
        $form->set('tipo_identificacion_comprador', '04')->call('guardar')
            ->assertHasErrors(['identificacion_comprador']);
        $this->assertDatabaseCount('facturas', 0);
    }

    #[Test]
    public function consumidor_final_admite_hasta_cincuenta_dolares(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        $form = Livewire::actingAs($usuario)->test(FacturaForm::class)
            ->set('razon_social_comprador', 'Consumidor final')
            ->set('correo_comprador', 'comprador@example.com')
            ->set('tipo_identificacion_comprador', '07')
            ->set('identificacion_comprador', '9999999999999')
            ->set('lineas.0.codigo_principal', 'A')
            ->set('lineas.0.descripcion', 'Producto')
            ->set('lineas.0.codigo_porcentaje', '0')
            ->set('lineas.0.precio_unitario', 50.01)
            ->call('guardar')->assertHasErrors(['identificacion_comprador']);
        $this->assertDatabaseCount('facturas', 0);
        $form->set('lineas.0.precio_unitario', 50)->call('guardar')->assertHasNoErrors();
        $this->assertDatabaseCount('facturas', 1);
    }
}
