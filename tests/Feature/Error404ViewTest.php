<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Error404ViewTest extends TestCase
{
    #[Test]
    public function ruta_inexistente_renderiza_vista_404_con_boton_volver(): void
    {
        $response = $this->get('/ruta-inexistente-facfa-404');

        $response->assertNotFound()
            ->assertSee('404')
            ->assertSee('Página no encontrada')
            ->assertSee('La página que buscas no existe, fue eliminada o su identificador no es válido.')
            ->assertSee('Volver al Dashboard')
            ->assertSeeHtml('href="'.route('dashboard').'"');

        $response->assertSeeHtml(view('partials.theme-script')->render())
            ->assertSeeHtml('bg-background text-foreground');
    }
}
