<?php

namespace Tests\Feature\Sri;

use App\Actions\Facturas\GuardarBorradorFacturaAction;
use App\Livewire\Facturas\FacturaForm;
use App\Livewire\Productos\ProductoForm;
use App\Models\Empresa;
use App\Models\FacturaDetalle;
use App\Models\Producto;
use App\Models\TarifaIva;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TarifaIvaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogosSriTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function los_formularios_ofrecen_el_catalogo_activo_con_exento_y_no_objeto(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('admin');

        foreach ([ProductoForm::class, FacturaForm::class] as $componente) {
            Livewire::actingAs($usuario)->test($componente)
                ->assertSee('No objeto de IVA')->assertSee('Exento de IVA')
                ->assertSee('13%')->assertSee('IVA diferenciado')
                ->assertDontSee('12%')->assertDontSee('14%');
        }
    }

    #[Test]
    #[DataProvider('tarifasActivas')]
    public function guarda_producto_con_codigo_y_porcentaje_del_catalogo(string $codigo, string $porcentaje): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('admin');

        Livewire::actingAs($usuario)->test(ProductoForm::class)
            ->set('codigo_principal', 'A')->set('descripcion', 'Producto sintético')
            ->set('precio_unitario', '10')->set('codigo_porcentaje', $codigo)
            ->call('guardar')->assertHasNoErrors();

        $producto = Producto::sole();
        $this->assertSame($codigo, $producto->codigo_porcentaje);
        $this->assertSame($porcentaje, $producto->tarifa);
    }

    public static function tarifasActivas(): array
    {
        return [['0', '0.00'], ['4', '15.00'], ['5', '5.00'], ['6', '0.00'],
            ['7', '0.00'], ['8', '8.00'], ['10', '13.00']];
    }

    #[Test]
    public function la_accion_conserva_codigos_distintos_con_porcentaje_cero_e_ignora_tarifa_manipulada(): void
    {
        $empresa = Empresa::factory()->create();
        $usuario = User::factory()->for($empresa)->create();
        $lineas = [];
        foreach (['0', '6', '7', '10'] as $codigo) {
            $lineas[] = ['codigo_principal' => $codigo, 'descripcion' => 'Sintético',
                'cantidad' => 1, 'precio_unitario' => 100, 'codigo_porcentaje' => $codigo, 'tarifa' => 99];
        }
        $factura = (new GuardarBorradorFacturaAction)->ejecutar(null, $empresa->id, $usuario->id, [
            'fecha_emision' => '2026-10-04', 'tipo_identificacion_comprador' => '06',
            'identificacion_comprador' => 'TEST', 'razon_social_comprador' => 'Comprador sintético',
        ], $lineas);

        $this->assertSame(['0', '6', '7', '10'], $factura->detalles->pluck('codigo_porcentaje')->all());
        $this->assertSame(['0.00', '0.00', '0.00', '13.00'], $factura->detalles->pluck('tarifa')->all());
        $this->assertSame('413.00', $factura->importe_total);
    }

    #[Test]
    public function el_seeder_es_idempotente_y_los_catalogos_sri_estan_completos(): void
    {
        $this->seed(TarifaIvaSeeder::class);
        $this->seed(TarifaIvaSeeder::class);
        $this->assertDatabaseCount('tarifas_iva', 9);
        $this->assertSame(['0', '4', '5', '6', '7', '8', '10'],
            TarifaIva::activas()->orderByRaw('CAST(codigo_porcentaje AS INTEGER)')->pluck('codigo_porcentaje')->all());
        $this->assertSame([1, 15, 16, 17, 18, 19, 20, 21], DB::table('formas_pago')->orderBy('codigo')->pluck('codigo')->all());
        $this->assertSame(['04', '05', '06', '07', '08'], DB::table('tipos_identificacion')->orderBy('codigo')->pluck('codigo')->all());
    }

    #[Test]
    public function la_migracion_rellena_productos_sin_alterar_importes_ni_codigos_historicos_y_es_reversible(): void
    {
        $migracion = require database_path('migrations/2026_10_04_000001_create_tarifas_iva_table.php');
        $detalle = FacturaDetalle::factory()->create(['codigo_porcentaje' => '7', 'tarifa' => '0.00']);
        $original = $detalle->fresh()->getRawOriginal();
        $migracion->down();
        $empresa = Empresa::factory()->create();
        foreach (['0.00', '5.00', '8.00', '12.00', '13.00', '14.00', '15.00'] as $i => $tarifa) {
            DB::table('productos')->insert(['empresa_id' => $empresa->id, 'codigo_principal' => 'M'.$i,
                'descripcion' => 'Histórico', 'precio_unitario' => '123.45', 'tarifa' => $tarifa]);
        }
        $migracion->up();
        $this->assertSame(['0', '5', '8', '2', '10', '3', '4'],
            Producto::orderBy('id')->pluck('codigo_porcentaje')->all());
        $this->assertSame($original, $detalle->fresh()->getRawOriginal());
        $migracion->down();
        $this->assertSame(['0.00', '5.00', '8.00', '12.00', '13.00', '14.00', '15.00'],
            Producto::orderBy('id')->pluck('tarifa')->all());
        $this->assertSame(array_fill(0, 7, '123.450000'), Producto::orderBy('id')->pluck('precio_unitario')->all());
        $migracion->up();
    }

    #[Test]
    public function producto_y_factura_rechazan_codigos_inactivos_o_inexistentes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('admin');
        foreach (['2', '3', '99'] as $codigo) {
            Livewire::actingAs($usuario)->test(ProductoForm::class)
                ->set('codigo_principal', 'A')->set('descripcion', 'Sintético')->set('precio_unitario', '10')
                ->set('codigo_porcentaje', $codigo)->call('guardar')->assertHasErrors(['codigo_porcentaje']);
            Livewire::actingAs($usuario)->test(FacturaForm::class)
                ->set('lineas.0.codigo_porcentaje', $codigo)->call('guardar')
                ->assertHasErrors(['lineas.0.codigo_porcentaje']);
        }
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('facturas', 0);
    }
}
