<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TonoNeutroTest extends TestCase
{
    #[Test]
    public function no_hay_voseo_en_vistas_y_componentes_clave(): void
    {
        $archivos = [
            resource_path('views/livewire/empresas/empresa-form.blade.php'),
            app_path('Livewire/Facturas/FacturaIndex.php'),
            resource_path('views/livewire/facturas/factura-form.blade.php'),
        ];

        foreach ($archivos as $archivo) {
            $this->assertFileExists($archivo);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(subí|querés|escribí|pedile|avisale|intentá|podés|acá)\b/iu',
                file_get_contents($archivo),
                "Se encontró voseo en {$archivo}",
            );
        }
    }
}
