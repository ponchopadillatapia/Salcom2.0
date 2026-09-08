<?php

namespace Tests\Unit\Services\Bancario;

use App\Services\Bancario\TitularNameMatcher;
use Tests\TestCase;

class TitularNameMatcherTest extends TestCase
{
    private TitularNameMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new TitularNameMatcher;
    }

    public function test_razon_social_con_sufijo_distinto_coincide(): void
    {
        $r = $this->matcher->comparar('INDUSTRIAS SALCOM S.A. DE C.V.', [
            'tipo_persona' => 'moral',
            'razon_social' => 'Industrias Salcom SA de CV',
        ]);

        $this->assertSame('aceptado', $r['decision']);
    }

    public function test_otra_empresa_se_rechaza(): void
    {
        $r = $this->matcher->comparar('OTRA EMPRESA COMERCIAL SA DE CV', [
            'tipo_persona' => 'moral',
            'razon_social' => 'Industrias Salcom SA de CV',
        ]);

        $this->assertSame('rechazado', $r['decision']);
    }

    public function test_persona_fisica_acepta_orden_invertido(): void
    {
        $r = $this->matcher->comparar('JUAN CARLOS PEREZ LOPEZ', [
            'tipo_persona' => 'fisica',
            'apellido_paterno' => 'Perez',
            'apellido_materno' => 'Lopez',
            'nombres' => 'Juan Carlos',
        ]);

        $this->assertSame('aceptado', $r['decision']);
    }

    public function test_persona_fisica_rechaza_apellido_distinto(): void
    {
        $r = $this->matcher->comparar('MARIA LOPEZ HERNANDEZ', [
            'tipo_persona' => 'fisica',
            'apellido_paterno' => 'Lopez',
            'apellido_materno' => 'Garcia',
            'nombres' => 'Maria',
        ]);

        $this->assertSame('rechazado', $r['decision']);
        $this->assertSame('apellido_materno_distinto', $r['motivo']);
    }

    public function test_rfc_de_caratula_acepta_aunque_el_nombre_este_truncado(): void
    {
        $r = $this->matcher->comparar('INDUSTRIAS SAL', [
            'tipo_persona' => 'moral',
            'razon_social' => 'Industrias Salcom SA de CV',
            'rfc' => 'SAL850101XX1',
            'rfc_caratula' => 'SAL850101XX1',
        ]);

        $this->assertSame('aceptado', $r['decision']);
        $this->assertSame('rfc', $r['motivo']);
    }

    public function test_sin_titular_pide_revision(): void
    {
        $r = $this->matcher->comparar(null, [
            'tipo_persona' => 'moral',
            'razon_social' => 'Industrias Salcom',
        ]);

        $this->assertSame('revision', $r['decision']);
        $this->assertSame('titular_no_extraido', $r['motivo']);
    }
}
