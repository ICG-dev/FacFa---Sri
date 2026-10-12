<?php

namespace Tests\Unit;

use App\Rules\CedulaEcuatoriana;
use App\Rules\RucEcuatoriano;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdentificacionEcuatorianaTest extends TestCase
{
    // Fixtures sintéticos construidos para probar el algoritmo; no proceden de registros de personas.
    public static function cedulas(): array
    {
        return [
            ['0100000009', true], ['2400000002', true], ['3000000004', true],
            ['0150000008', true], ['0100000090', true],
            ['0100000008', false], ['010000000', false], ['01000000090', false],
            ['0000000000', false], ['2500000001', false], ['3100000000', false],
            ['0160000006', false], ['0190000000', false],
            ['010000000A', false], ['0100000009 ', false], ["0100000009\n", false],
        ];
    }

    public static function rucs(): array
    {
        return [
            ['0100000009001', true], ['2400000002002', true], ['3000000004999', true],
            ['0190000000002', true], ['2490000000003', true], ['3090000000001', true],
            ['0160000090002', true], ['2460000090001', true],
            ['0100000008001', false], ['0100000009000', false], ['0190000000000', false],
            ['0160000090000', false], ['0090000000001', false], ['2590000000001', false],
            ['3170000000001', false], ['0170000000001', false], ['0180000000001', false],
            ['019000000001', false], ['01900000000011', false], ['019000000000A', false],
            ['0190000000002 ', false],
        ];
    }

    #[Test]
    #[DataProvider('cedulas')]
    public function valida_cedula_con_provincia_tercer_digito_y_modulo_diez(string $valor, bool $valido): void
    {
        $validator = Validator::make(['id' => $valor], ['id' => [new CedulaEcuatoriana]]);
        $this->assertSame($valido, $validator->passes());
        if (! $valido) {
            $this->assertStringContainsString('cédula', $validator->errors()->first('id'));
        }
    }

    #[Test]
    #[DataProvider('rucs')]
    public function valida_ruc_sin_imponer_modulo_once_a_sociedades(string $valor, bool $valido): void
    {
        $validator = Validator::make(['id' => $valor], ['id' => [new RucEcuatoriano]]);
        $this->assertSame($valido, $validator->passes());
        if (! $valido) {
            $this->assertStringContainsString('RUC', $validator->errors()->first('id'));
        }
    }
}
