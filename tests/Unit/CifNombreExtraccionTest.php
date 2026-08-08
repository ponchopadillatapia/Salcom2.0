<?php

namespace Tests\Unit;

use App\Http\Controllers\APIS\EmpresaApiController;
use ReflectionMethod;
use Tests\TestCase;

class CifNombreExtraccionTest extends TestCase
{
    private function extraerNombre(string $texto): ?string
    {
        $controller = new EmpresaApiController;
        $method = new ReflectionMethod($controller, 'extraerNombreCifPersonaFisica');
        $method->setAccessible(true);

        return $method->invoke($controller, strtoupper($texto));
    }

    public function test_extrae_nombre_con_etiquetas_limpias(): void
    {
        $texto = 'NOMBRE(S): CARLOS ISAAC PRIMER APELLIDO: TELLEZ SEGUNDO APELLIDO: GONZALEZ';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        $this->assertStringContainsString('TELLEZ', $resultado);
        $this->assertStringContainsString('GONZALEZ', $resultado);
        $this->assertStringContainsString('CARLOS', $resultado);
    }

    public function test_extrae_nombre_con_etiquetas_pegadas(): void
    {
        $texto = 'PRIMERAPELLIDO TELLEZ SEGUNDOAPELLIDO GONZALEZ NOMBRE CARLOSISAAC';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        $this->assertStringContainsString('TELLEZ', $resultado);
        $this->assertStringContainsString('GONZALEZ', $resultado);
        // CARLOSISAAC debería separarse a CARLOS ISAAC
        $this->assertStringContainsString('CARLOS', $resultado);
    }

    public function test_no_incluye_etiquetas_sat_en_nombre(): void
    {
        $texto = 'PRIMER APELLIDO: TELLEZ SEGUNDO APELLIDO: GONZALEZ NOMBRE(S): CARLOS ISAAC FECHA INICIO DE OPERACIONES: 12 DE AGOSTO';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        $this->assertStringNotContainsString('FECHA', $resultado);
        $this->assertStringNotContainsString('INICIO', $resultado);
        $this->assertStringNotContainsString('OPERACIONES', $resultado);
        $this->assertStringNotContainsString('AGOSTO', $resultado);
    }

    public function test_no_incluye_fechas_numericas(): void
    {
        $texto = 'PRIMER APELLIDO: TELLEZ SEGUNDO APELLIDO: GONZALEZ NOMBRE(S): CARLOS 21/11/1994';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        $this->assertStringNotContainsString('21/11/1994', $resultado);
        $this->assertStringNotContainsString('1994', $resultado);
    }

    public function test_formato_apellido_paterno_materno_nombres(): void
    {
        $texto = 'PRIMER APELLIDO: LOPEZ SEGUNDO APELLIDO: HERNANDEZ NOMBRE(S): JUAN PEDRO';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        // El formato debe ser APELLIDO1 APELLIDO2 NOMBRE(S)
        $this->assertEquals('LOPEZ HERNANDEZ JUAN PEDRO', $resultado);
    }

    public function test_maneja_texto_con_mucha_basura(): void
    {
        $texto = 'DATOS DE IDENTIFICACION DEL CONTRIBUYENTE RFC TEGC941121QN1 CURP TEGC941121HJCLNR04 NOMBRE(S) CARLOS ISAAC PRIMER APELLIDO TELLEZ SEGUNDO APELLIDO GONZALEZ FECHA INICIO DE OPERACIONES 12 DE AGOSTO DE 2014 ESTATUS EN EL PADRON ACTIVO';
        $resultado = $this->extraerNombre($texto);
        $this->assertNotNull($resultado);
        $this->assertStringContainsString('TELLEZ', $resultado);
        $this->assertStringContainsString('GONZALEZ', $resultado);
        $this->assertStringContainsString('CARLOS', $resultado);
        $this->assertStringNotContainsString('FECHA', $resultado);
        $this->assertStringNotContainsString('PADRON', $resultado);
        $this->assertStringNotContainsString('ACTIVO', $resultado);
    }

    public function test_separa_palabras_pegadas(): void
    {
        $controller = new EmpresaApiController;
        $method = new ReflectionMethod($controller, 'separarPalabrasPegadas');
        $method->setAccessible(true);

        $this->assertEquals('CARLOS ISAAC', $method->invoke($controller, 'CARLOSISAAC'));
        $this->assertEquals('MARIA GUADALUPE', $method->invoke($controller, 'MARIAGUADALUPE'));
        $this->assertEquals('JOSE LUIS', $method->invoke($controller, 'JOSELUIS'));
        // Palabra corta no se separa
        $this->assertEquals('TELLEZ', $method->invoke($controller, 'TELLEZ'));
    }

    public function test_retorna_null_si_no_hay_nombre(): void
    {
        $texto = 'CONSTANCIA DE SITUACION FISCAL RFC SERVICIO DE ADMINISTRACION TRIBUTARIA';
        $resultado = $this->extraerNombre($texto);
        $this->assertNull($resultado);
    }
}
