<?php

namespace Tests\Unit\Models;

use App\Models\Factura;
use Carbon\Carbon;
use Tests\TestCase;

class FacturaDiasRestantesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_contador_baja_un_dia_al_pasar_la_fecha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00'));

        $factura = new Factura([
            'fecha_vencimiento' => Carbon::parse('2026-08-17')->addDays(320)->toDateString(),
            'dias_plazo' => 320,
        ]);

        $this->assertSame(320, $factura->diasRestantes());

        Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00'));
        $this->assertSame(319, $factura->diasRestantes());
    }

    public function test_vence_hoy_y_luego_queda_vencida(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 15:00:00'));

        $factura = new Factura([
            'fecha_vencimiento' => '2026-08-17',
        ]);

        $this->assertSame(0, $factura->diasRestantes());

        Carbon::setTestNow(Carbon::parse('2026-08-18 00:00:00'));
        $this->assertSame(-1, $factura->diasRestantes());
    }
}
