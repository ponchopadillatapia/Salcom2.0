<?php

namespace Tests\Unit\Services\Bancario;

use App\Services\Bancario\CaratulaCampoExtractor;
use Tests\TestCase;

class CaratulaCampoExtractorTest extends TestCase
{
    public function test_extrae_titular_clabe_y_rfc(): void
    {
        $extractor = new CaratulaCampoExtractor;
        $campos = $extractor->extraer(
            "Carátula BBVA\nTitular: Industrias Salcom S.A. de C.V.\nRFC: SAL850101XX1\nCLABE: 012180001234567891"
        );

        $this->assertSame('INDUSTRIAS SALCOM S.A. DE C.V', $campos['titular']);
        $this->assertSame('SAL850101XX1', $campos['rfc']);
        $this->assertSame('012180001234567891', $campos['clabe']);
    }

    public function test_no_toma_nombre_del_banco_como_titular(): void
    {
        $extractor = new CaratulaCampoExtractor;
        $titular = $extractor->extraerTitular('NOMBRE DEL BANCO: BBVA MEXICO CLABE 012180001234567891');

        $this->assertNull($titular);
    }
}
