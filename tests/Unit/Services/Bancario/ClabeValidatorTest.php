<?php

namespace Tests\Unit\Services\Bancario;

use App\Services\Bancario\ClabeValidator;
use Tests\TestCase;

class ClabeValidatorTest extends TestCase
{
    private ClabeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ClabeValidator;
    }

    public function test_acepta_clabe_con_digito_verificador_correcto(): void
    {
        $clabe = $this->clabeBbva('18000123456789');

        $this->assertTrue($this->validator->esFormatoValido($clabe));
        $this->assertTrue($this->validator->tieneChecksumValido($clabe));
        $this->assertSame('012', $this->validator->codigoBanco($clabe));
        $this->assertSame('BBVA', $this->validator->nombreBanco($clabe));
    }

    public function test_rechaza_clabe_con_digito_verificador_incorrecto(): void
    {
        $this->assertFalse($this->validator->tieneChecksumValido('012345678901234567'));
    }

    public function test_banco_declarado_coincide_con_prefijo(): void
    {
        $clabe = $this->clabeBbva('18000123456789');

        $this->assertTrue($this->validator->bancoCoincideConClabe($clabe, 'BBVA'));
        $this->assertTrue($this->validator->bancoCoincideConClabe($clabe, 'BBVA México'));
        $this->assertFalse($this->validator->bancoCoincideConClabe($clabe, 'Santander'));
    }

    public function test_prefijo_desconocido_no_bloquea(): void
    {
        $base = '99918000123456789';
        $clabe = $base.$this->validator->digitoVerificador($base);

        $this->assertTrue($this->validator->tieneChecksumValido($clabe));
        $this->assertTrue($this->validator->bancoCoincideConClabe($clabe, 'Banco Inventado'));
    }

    private function clabeBbva(string $plazaYCuenta14): string
    {
        $base = '012'.$plazaYCuenta14;
        $this->assertSame(17, strlen($base));

        return $base.$this->validator->digitoVerificador($base);
    }
}
