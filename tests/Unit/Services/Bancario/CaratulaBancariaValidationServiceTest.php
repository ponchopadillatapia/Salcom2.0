<?php

namespace Tests\Unit\Services\Bancario;

use App\Services\Bancario\CaratulaBancariaValidationService;
use App\Services\Bancario\ClabeValidator;
use Tests\TestCase;

class CaratulaBancariaValidationServiceTest extends TestCase
{
    private CaratulaBancariaValidationService $service;

    private ClabeValidator $clabe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CaratulaBancariaValidationService;
        $this->clabe = new ClabeValidator;
    }

    public function test_acepta_cuando_clabe_y_titular_coinciden(): void
    {
        $clabe = $this->clabeBbva();
        $texto = "BBVA MEXICO\nTITULAR: INDUSTRIAS SALCOM SA DE CV\nCLABE: {$clabe}";

        $r = $this->service->enriquecer(
            ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => []],
            $texto,
            [
                'clabe' => $clabe,
                'banco' => 'BBVA',
                'tipo_persona' => 'moral',
                'razon_social' => 'Industrias Salcom S.A. de C.V.',
            ]
        );

        $this->assertTrue($r['ok']);
        $this->assertSame('aceptado', $r['decision']);
        $this->assertSame($clabe, $r['datos']['clabe']);
        $this->assertTrue($r['datos']['clabe_checksum_ok']);
    }

    public function test_rechaza_cuando_el_titular_es_otra_persona(): void
    {
        $clabe = $this->clabeBbva();
        $texto = "BBVA\nTITULAR: JUAN PEREZ GARCIA\nCLABE {$clabe}";

        $r = $this->service->enriquecer(
            ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => []],
            $texto,
            [
                'clabe' => $clabe,
                'banco' => 'BBVA',
                'tipo_persona' => 'moral',
                'razon_social' => 'Industrias Salcom SA de CV',
            ]
        );

        $this->assertFalse($r['ok']);
        $this->assertSame('rechazado', $r['decision']);
        $this->assertNotEmpty($r['errores']);
    }

    public function test_rechaza_clabe_distinta_aunque_el_titular_coincida(): void
    {
        $clabePdf = $this->clabeBbva();
        $clabeOtra = $this->clabeSantander();
        $texto = "TITULAR: INDUSTRIAS SALCOM SA DE CV\nCLABE: {$clabePdf}";

        $r = $this->service->enriquecer(
            ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => []],
            $texto,
            [
                'clabe' => $clabeOtra,
                'banco' => 'BBVA',
                'tipo_persona' => 'moral',
                'razon_social' => 'Industrias Salcom SA de CV',
            ]
        );

        $this->assertSame('rechazado', $r['decision']);
        $this->assertTrue(collect($r['errores'])->contains(fn (string $e) => str_contains($e, 'no coincide')));
    }

    public function test_sin_titular_legible_no_auto_aprueba(): void
    {
        $clabe = $this->clabeBbva();
        $texto = "ESTADO DE CUENTA BBVA\nCLABE: {$clabe}";

        $r = $this->service->enriquecer(
            ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => []],
            $texto,
            [
                'clabe' => $clabe,
                'banco' => 'BBVA',
                'tipo_persona' => 'moral',
                'razon_social' => 'Industrias Salcom SA de CV',
            ]
        );

        $this->assertFalse($r['ok']);
        $this->assertSame('revision', $r['decision']);
        $this->assertSame('titular_no_extraido', $r['datos']['titular_motivo']);
    }

    private function clabeBbva(): string
    {
        $base = '01218000123456789';

        return $base.$this->clabe->digitoVerificador($base);
    }

    private function clabeSantander(): string
    {
        $base = '01418000123456789';

        return $base.$this->clabe->digitoVerificador($base);
    }
}
