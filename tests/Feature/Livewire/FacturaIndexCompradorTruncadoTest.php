<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Facturas\FacturaIndex;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacturaIndexCompradorTruncadoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('nombresComprador')]
    public function celda_de_comprador_trunca_con_tooltip_del_nombre_completo(string $nombre): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $usuario = User::factory()->for(Empresa::factory())->create();
        $usuario->assignRole('facturador');
        Factura::factory()->create([
            'empresa_id' => $usuario->empresa_id,
            'razon_social_comprador' => $nombre,
        ]);

        $componente = Livewire::actingAs($usuario)->test(FacturaIndex::class)
            ->assertSeeHtml('title="'.e($nombre).'"');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<meta charset="UTF-8">'.$componente->html());
        $xpath = new \DOMXPath($dom);
        $compradores = $xpath->query('//td/div[contains(concat(" ", normalize-space(@class), " "), " truncate ")]');

        $this->assertSame(1, $compradores->length);
        $comprador = $compradores->item(0);
        $this->assertStringContainsString('max-w-xs', $comprador->getAttribute('class'));
        $this->assertSame($nombre, $comprador->getAttribute('title'));
        $this->assertSame($nombre, $comprador->textContent);
        $this->assertSame(0, $comprador->childElementCount);
    }

    public static function nombresComprador(): array
    {
        return [
            'razón social larga' => ['COMPAÑÍA DE SERVICIOS INTEGRALES ECUATORIANOS INTERNACIONALES S.A.'],
            'caracteres HTML y comillas' => ['EMPRESA "ESPECIAL" & <script>alert(1)</script> S.A.'],
        ];
    }
}
