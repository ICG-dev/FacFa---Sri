<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarThemeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sidebar_no_usa_text_white_fijo_para_nombres(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $empresa = Empresa::factory()->create(['razon_social' => 'Empresa de prueba de tema']);
        $usuario = User::factory()->for($empresa)->create();
        $usuario->assignRole('admin');

        $response = $this->actingAs($usuario)->get(route('dashboard'))->assertOk();
        $response->assertDontSeeHtml('text-white truncate');

        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($documento);
        foreach ([config('app.name'), $empresa->razon_social] as $nombre) {
            $elemento = $xpath->query('//aside//div[normalize-space(text())="'.$nombre.'"]')->item(0);
            $this->assertNotNull($elemento);
            $this->assertStringContainsString('text-sidebar-foreground', $elemento->getAttribute('class'));
        }
    }

    #[Test]
    public function sidebar_claro_comparte_la_paleta_del_contenido(): void
    {
        $paleta = $this->paleta(':root');

        $this->assertSame($paleta['card'], $paleta['sidebar']);
        $this->assertSame($paleta['foreground'], $paleta['sidebar-foreground']);
        $this->assertSame($paleta['border'], $paleta['sidebar-border']);
        $this->assertSame($paleta['secondary'], $paleta['sidebar-accent']);
        $this->assertSame($paleta['primary'], $paleta['sidebar-accent-foreground']);
    }

    #[Test]
    #[DataProvider('temas')]
    public function texto_secundario_supera_contraste_aa_en_las_superficies_del_tema(string $tema): void
    {
        $paleta = $this->paleta($tema);
        $texto = $this->luminancia($paleta['muted-foreground']);

        foreach (['background', 'card', 'sidebar', 'muted', 'secondary'] as $superficie) {
            $fondo = $this->luminancia($paleta[$superficie]);
            $contraste = (max($texto, $fondo) + 0.05) / (min($texto, $fondo) + 0.05);
            $this->assertGreaterThan(4.5, $contraste, "$tema: texto secundario sobre $superficie");
        }
    }

    public static function temas(): array
    {
        return [[':root'], ['.dark']];
    }

    #[Test]
    #[DataProvider('temas')]
    public function enlace_del_404_tiene_contraste_aa_en_ambos_temas(string $tema): void
    {
        $response = $this->get('/ruta-inexistente-facfa-404')->assertNotFound();
        $documento = new \DOMDocument;
        @$documento->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $enlace = (new \DOMXPath($documento))->query('//a[@href="'.route('dashboard').'"]')->item(0);
        $this->assertNotNull($enlace);
        preg_match('/\bbg-([a-z-]+)\b/', $enlace->getAttribute('class'), $fondo);
        preg_match('/\btext-([a-z-]+)\b/', $enlace->getAttribute('class'), $texto);
        $paleta = $this->paleta($tema);
        $luminanciaFondo = $this->luminancia($paleta[$fondo[1]]);
        $luminanciaTexto = $this->luminancia($paleta[$texto[1]]);
        $contraste = (max($luminanciaFondo, $luminanciaTexto) + 0.05) / (min($luminanciaFondo, $luminanciaTexto) + 0.05);

        $this->assertGreaterThanOrEqual(4.5, $contraste, "$tema: enlace de retorno del 404");
    }

    private function paleta(string $selector): array
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertSame(1, preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]+)\}/s', $css, $bloque));
        preg_match_all('/--([\w-]+):\s*([^;]+);/', $bloque[1], $variables, PREG_SET_ORDER);

        return array_column($variables, 2, 1);
    }

    private function luminancia(string $hex): float
    {
        $canales = array_map(function (string $canal): float {
            $valor = hexdec($canal) / 255;

            return $valor <= 0.04045 ? $valor / 12.92 : (($valor + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
    }
}
