<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationBreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('paginas')]
    public function muestra_migas_contextuales_y_enlaces_intermedios(string $ruta, string $titulo, ?string $rutaPadre): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->create();
        $usuario->assignRole('admin');
        $parametros = match ($ruta) {
            'facturas.edit', 'facturas.show' => ['factura' => Factura::factory()->create([
                'empresa_id' => $usuario->empresa_id, 'user_id' => $usuario->id,
            ])],
            'productos.edit' => ['producto' => Producto::factory()->create(['empresa_id' => $usuario->empresa_id])],
            'usuarios.edit' => ['usuario' => $usuario],
            'empresas.edit' => ['empresa' => $usuario->empresa_id],
            default => [],
        };

        $response = $this->actingAs($usuario)->get(route($ruta, $parametros))->assertOk();
        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($documento);
        $migas = $xpath->query('//header//nav[@aria-label="Migas de pan"]');
        $this->assertCount(1, $migas, 'La navegación debe identificar sus migas de pan.');
        $nav = $migas->item(0);
        $this->assertStringContainsString('Sistema', $nav->textContent);
        $actual = $xpath->query('.//*[@aria-current="page"]', $nav);
        $this->assertCount(1, $actual);
        $this->assertSame($titulo, trim($actual->item(0)->textContent));
        if ($rutaPadre) {
            $padre = $xpath->query('.//a[@href="'.route($rutaPadre).'"]', $nav);
            $this->assertCount(1, $padre, 'La miga del módulo debe enlazar a su listado.');
        }
    }

    public static function paginas(): array
    {
        return [
            ['dashboard', 'Dashboard', null],
            ['facturas.index', 'Facturas', null],
            ['facturas.create', 'Nueva factura', 'facturas.index'],
            ['facturas.edit', 'Editar factura', 'facturas.index'],
            ['facturas.show', 'Detalle', 'facturas.index'],
            ['productos.index', 'Productos', null],
            ['productos.create', 'Nuevo producto', 'productos.index'],
            ['productos.edit', 'Editar producto', 'productos.index'],
            ['usuarios.index', 'Usuarios', null],
            ['usuarios.create', 'Nuevo usuario', 'usuarios.index'],
            ['usuarios.edit', 'Editar usuario', 'usuarios.index'],
            ['empresas.edit', 'Mi empresa', null],
            ['profile', 'Perfil', null],
        ];
    }

    #[Test]
    #[DataProvider('paginasEmpresas')]
    public function paginas_de_empresas_muestran_mi_empresa(string $ruta): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->create(['empresa_id' => null]);
        $usuario->assignRole('super_admin');

        $response = $this->actingAs($usuario)->get(route($ruta))->assertOk();
        $documento = new \DOMDocument;
        @$documento->loadHTML($response->getContent());
        $xpath = new \DOMXPath($documento);
        $actual = $xpath->query('//header//nav[@aria-label="Migas de pan"]//*[@aria-current="page"]');
        $this->assertCount(1, $actual);
        $this->assertSame('Mi empresa', trim($actual->item(0)->textContent));
    }

    public static function paginasEmpresas(): array
    {
        return [['empresas.index'], ['empresas.create']];
    }
}
