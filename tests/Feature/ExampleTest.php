<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_redirige_a_login_para_visitantes(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_la_raiz_redirige_al_dashboard_si_ya_esta_logueado(): void
    {
        $usuario = User::factory()->create(['empresa_id' => null]);

        $response = $this->actingAs($usuario)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
