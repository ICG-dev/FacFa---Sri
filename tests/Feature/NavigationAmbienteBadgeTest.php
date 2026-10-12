<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationAmbienteBadgeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('ambientes')]
    public function muestra_badge_del_ambiente_de_su_empresa(int $ambiente, string $etiqueta, string $otra): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory()->state(['ambiente' => $ambiente]))->create();
        $usuario->assignRole('facturador');
        Empresa::factory()->create(['ambiente' => $ambiente === 1 ? 2 : 1]);

        $response = $this->actingAs($usuario)->get(route('dashboard'))->assertOk();
        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $header = (new \DOMXPath($documento))->query('//header')->item(0);
        $this->assertNotNull($header);
        $this->assertStringContainsString($etiqueta, $header->textContent);
        $this->assertStringNotContainsString($otra, $header->textContent);
    }

    public static function ambientes(): array
    {
        return [[1, 'PRUEBAS', 'PRODUCCIÓN'], [2, 'PRODUCCIÓN', 'PRUEBAS']];
    }

    #[Test]
    public function usuario_sin_empresa_no_muestra_un_ambiente(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->create(['empresa_id' => null]);
        $usuario->assignRole('super_admin');

        $this->actingAs($usuario)->get(route('empresas.index'))
            ->assertOk()
            ->assertDontSee('PRUEBAS')
            ->assertDontSee('PRODUCCIÓN');
    }
}
