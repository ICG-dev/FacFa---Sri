<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Usuarios\UsuarioForm;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsuarioFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('modosFormulario')]
    public function formulario_amplio_orienta_la_creacion_y_edicion_sin_rellenar_la_clave(bool $editar): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->for($empresa)->create();
        $admin->assignRole('admin');
        $parametros = $editar
            ? ['usuario' => User::factory()->for($empresa)->create()]
            : [];

        $componente = Livewire::actingAs($admin)
            ->test(UsuarioForm::class, $parametros)
            ->assertSet('password', '')
            ->assertSeeHtml('placeholder="Ej. Carlos Mendoza"')
            ->assertSeeHtml('placeholder="carlos@empresa.com"')
            ->assertSeeHtml('placeholder="••••••••"')
            ->assertSeeHtml('autocomplete="new-password"');

        $dom = new \DOMDocument;
        @$dom->loadHTML('<meta charset="UTF-8">'.$componente->html());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " max-w-2xl ")]/form')->length);
    }

    public static function modosFormulario(): array
    {
        return [
            'nuevo usuario' => [false],
            'editar usuario' => [true],
        ];
    }
}
