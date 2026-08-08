<?php

namespace Tests\Unit;

use App\Services\DocumentCrossCheckService;
use Tests\TestCase;

class DocumentCrossCheckServiceTest extends TestCase
{
    private DocumentCrossCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentCrossCheckService;
    }

    // ═══════════════════════════════════════
    // NORMALIZACIÓN
    // ═══════════════════════════════════════

    public function test_normaliza_texto_correctamente(): void
    {
        $this->assertEquals('CARLOS ISAAC TELLEZ GONZALEZ', $this->service->normalizar('Carlos Isaac Téllez González'));
        $this->assertEquals('MEGAIMPRESOS TULTITLAN', $this->service->normalizar('MEGAIMPRESOS TULTITLÁN'));
        $this->assertEquals('JOSE MARIA NUNEZ', $this->service->normalizar('José María Ñúñez'));
        $this->assertEquals('', $this->service->normalizar(null));
        $this->assertEquals('RFC123456ABC', $this->service->normalizar('RFC-123456-ABC'));
    }

    // ═══════════════════════════════════════
    // RFC ↔ CURP
    // ═══════════════════════════════════════

    public function test_rfc_coincide_con_curp(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC']
        );

        $this->assertTrue($result['checks']['rfc_curp']['coincide']);
    }

    public function test_rfc_no_coincide_con_curp_de_otra_persona(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS'],
            ['curp' => 'MEGM770927HMCRRR01', 'nombre' => 'MERCADO GARCIA MARIO']
        );

        $this->assertFalse($result['checks']['rfc_curp']['coincide']);
        $this->assertFalse($result['valido']);
    }

    // ═══════════════════════════════════════
    // NOMBRES
    // ═══════════════════════════════════════

    public function test_nombres_exactos_coinciden(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC']
        );

        $this->assertTrue($result['checks']['nombre']['coincide']);
        $this->assertEquals(100, $result['checks']['nombre']['similitud']);
    }

    public function test_nombres_con_diferente_orden_coinciden(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'CARLOS ISAAC TELLEZ GONZALEZ'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC']
        );

        $this->assertTrue($result['checks']['nombre']['coincide']);
    }

    public function test_nombres_con_variacion_ocr_coinciden(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAC'], // falta una A
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS ISAAC']
        );

        // Debe coincidir porque la similitud es > 90%
        $this->assertTrue($result['checks']['nombre']['similitud'] >= 90);
    }

    public function test_nombres_completamente_diferentes_no_coinciden(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'LOPEZ HERNANDEZ JUAN PEDRO']
        );

        $this->assertFalse($result['checks']['nombre']['coincide']);
    }

    // ═══════════════════════════════════════
    // CASO DE FRAUDE: DOCUMENTOS DE OTRO PROVEEDOR
    // ═══════════════════════════════════════

    public function test_detecta_documentos_de_diferente_persona(): void
    {
        // CIF de una persona, INE de otra
        $result = $this->service->validar(
            ['rfc' => 'VPA211201F67', 'nombre' => 'VERTICALE PLATAFORMAS SA DE CV'],
            ['curp' => 'MEGM770927HMCRRR01', 'nombre' => 'MERCADO GARCIA MARIO ALBERTO']
        );

        $this->assertFalse($result['valido']);
        $this->assertFalse($result['checks']['rfc_curp']['coincide']);
        $this->assertFalse($result['checks']['nombre']['coincide']);
        $this->assertNotEmpty($result['errores']);
    }

    // ═══════════════════════════════════════
    // CÓDIGO POSTAL (ALERTA, NO BLOQUEA)
    // ═══════════════════════════════════════

    public function test_codigo_postal_diferente_genera_alerta_pero_no_bloquea(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS', 'codigo_postal' => '45645'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS', 'codigo_postal' => '44100']
        );

        // Genera alerta
        $this->assertTrue($result['checks']['codigo_postal']['postal_code_mismatch']);
        $this->assertNotEmpty($result['alertas']);
        // Pero no bloquea la validación (si RFC y nombre coinciden)
        $this->assertTrue($result['valido']);
    }

    // ═══════════════════════════════════════
    // SCORE
    // ═══════════════════════════════════════

    public function test_score_perfecto_cuando_todo_coincide(): void
    {
        $result = $this->service->validar(
            ['rfc' => 'TEGC941121QN1', 'nombre' => 'TELLEZ GONZALEZ CARLOS', 'codigo_postal' => '45645'],
            ['curp' => 'TEGC941121HJCLNR04', 'nombre' => 'TELLEZ GONZALEZ CARLOS', 'codigo_postal' => '45645']
        );

        $this->assertGreaterThanOrEqual(90, $result['score']);
        $this->assertTrue($result['valido']);
    }

    public function test_datos_faltantes_no_causan_excepcion(): void
    {
        $result = $this->service->validar(
            ['rfc' => null, 'nombre' => null],
            ['curp' => null, 'nombre' => null]
        );

        $this->assertFalse($result['valido']);
        $this->assertIsArray($result['checks']);
    }
}
