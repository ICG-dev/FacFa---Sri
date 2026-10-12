<?php

namespace Tests\Feature\Sri;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class RutasConstraintTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function facturas_nueva_redirige_a_facturas_crear(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/facturas/nueva')
            ->assertRedirect(route('facturas.create'));
    }

    #[Test]
    #[DataProvider('rutasInvalidas')]
    public function parametro_no_numerico_en_factura_responde_404_y_no_500(string $ruta): void
    {
        $this->actingAs(User::factory()->create())->get($ruta)->assertNotFound();
    }

    #[Test]
    #[DataProvider('rutasInvalidas')]
    public function rutas_invalidas_se_rechazan_antes_de_resolver_el_modelo(string $ruta): void
    {
        $this->expectException(NotFoundHttpException::class);

        Route::getRoutes()->match(Request::create($ruta));
    }

    public static function rutasInvalidas(): array
    {
        return [
            ['/facturas/invalido'],
            ['/facturas/invalido/editar'],
            ['/facturas/invalido/ride'],
            ['/facturas/invalido/xml'],
        ];
    }
}
