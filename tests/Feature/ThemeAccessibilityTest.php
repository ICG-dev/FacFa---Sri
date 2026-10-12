<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->usuario = User::factory()->for(Empresa::factory())->create();
        $this->usuario->assignRole('admin');
    }

    #[Test]
    public function boton_de_tema_tiene_aria_label(): void
    {
        $this->actingAs($this->usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeHtml('aria-label="'.__('Cambiar tema').'"');
    }

    #[Test]
    public function notificaciones_cuenta_con_dropdown_accesible(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('dashboard'))->assertOk();
        $response->assertSee('Notificaciones')
            ->assertSee('No tienes notificaciones pendientes')
            ->assertSee('Te avisaremos aquí si el SRI rechaza o devuelve algún comprobante.');

        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($documento);
        $boton = $xpath->query('//header//button[@aria-label="Notificaciones"]')->item(0);
        $this->assertNotNull($boton);
        $this->assertSame('button', $boton->getAttribute('type'));
        $this->assertSame('open.toString()', $boton->getAttribute('x-bind:aria-expanded'));

        $panel = $xpath->query('//*[@id="'.$boton->getAttribute('aria-controls').'"]')->item(0);
        $this->assertNotNull($panel);
        $this->assertStringContainsString('No tienes notificaciones pendientes', $panel->textContent);
        $this->assertSame('region', $panel->getAttribute('role'));
        $this->assertNotNull($xpath->query('//*[@id="'.$panel->getAttribute('aria-labelledby').'"]')->item(0));
    }
}
