<?php

namespace Tests\Feature;

use App\Http\Controllers\PortalProveedorController;
use App\Models\AnticipoProveedor;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Flujo anticipo → nota de crédito (forma 30) → factura final, SIN importar el orden de subida.
 *
 * Reglas confirmadas con Said:
 *  - El anticipo lo sube el proveedor por el portal y YA está pagado -> se registra solo.
 *  - La factura final (TipoRelacion 07) relaciona el UUID del anticipo -> el anticipo se aplica.
 *  - La NC forma 30 "mata" el anticipo (saldo a 0, estatus 'aplicado').
 *  - Pase lo que pase con el orden, el dinero no se duplica.
 *
 * UUIDs del caso real:
 *   ANTICIPO   = 8A41F661-3D88-40C4-B8EE-62883D5844D4
 *   FACT FINAL = 6360C586-2B98-41EE-97F7-CA2A1FA038E2 (relaciona el anticipo, 07)
 *   NC (30)    = relaciona la factura final
 */
class FlujoAnticipoNotaCreditoTest extends TestCase
{
    use RefreshDatabase;

    private const COD = 'M213008002';
    private const UUID_ANT = '8A41F661-3D88-40C4-B8EE-62883D5844D4';
    private const UUID_FIN = '6360C586-2B98-41EE-97F7-CA2A1FA038E2';
    private const MONTO = 992380.00;

    private function invocar(string $metodo, array $args): void
    {
        $c = app(PortalProveedorController::class);
        $m = new ReflectionMethod($c, $metodo);
        $m->setAccessible(true);
        $m->invokeArgs($c, $args);
    }

    /** Crea (simula subir) la factura-anticipo y dispara su procesamiento. */
    private function subirAnticipo(): Factura
    {
        $f = Factura::create([
            'folio_cfdi' => 'INV2249', 'uuid_cfdi' => self::UUID_ANT, 'codigo_proveedor' => self::COD,
            'monto' => 855500.00, 'monto_iva' => 136880.00, 'total' => self::MONTO, 'monto_pagado' => 0, 'estatus' => 'pendiente',
            'validacion_detalle' => ['cfdi_relacionados' => [], 'tipo_relacion' => null],
        ]);
        $datos = [
            'uuid' => self::UUID_ANT, 'subtotal' => 855500.00, 'iva' => 136880.00, 'total' => self::MONTO,
            'serie' => 'INV', 'folio' => '2249', 'numero_oc' => 'MPA6649',
            'conceptos_detalle' => [['clave_prod_serv' => '84111506']],
            'cfdi_relacionados' => [], 'tipo_relacion' => null, 'es_nota_credito' => false,
        ];
        $this->invocar('procesarFlujoAnticipos', [$f, $datos, self::COD]);

        return $f;
    }

    /** Crea (simula subir) la factura final que relaciona el anticipo. */
    private function subirFacturaFinal(): Factura
    {
        $f = Factura::create([
            'folio_cfdi' => 'INV2345', 'uuid_cfdi' => self::UUID_FIN, 'codigo_proveedor' => self::COD,
            'monto' => 855500.00, 'monto_iva' => 136880.00, 'total' => self::MONTO, 'monto_pagado' => 0, 'estatus' => 'pendiente',
            'validacion_detalle' => ['cfdi_relacionados' => [self::UUID_ANT], 'tipo_relacion' => '07'],
        ]);
        $datos = [
            'uuid' => self::UUID_FIN, 'subtotal' => 855500.00, 'iva' => 136880.00, 'total' => self::MONTO,
            'serie' => 'INV', 'folio' => '2345',
            'conceptos_detalle' => [['clave_prod_serv' => '12161900']],
            'cfdi_relacionados' => [self::UUID_ANT], 'tipo_relacion' => '07', 'es_nota_credito' => false,
        ];
        $this->invocar('procesarFlujoAnticipos', [$f, $datos, self::COD]);

        return $f;
    }

    /** Crea (simula subir) la NC forma 30 que relaciona la factura final. */
    private function subirNotaCredito(): Factura
    {
        $f = Factura::create([
            'folio_cfdi' => 'INVNC198', 'uuid_cfdi' => 'DD856DAF-954C-40E9-A2FA-C8A17FE86A4D',
            'codigo_proveedor' => self::COD, 'monto' => 855500.00, 'monto_iva' => 136880.00, 'total' => self::MONTO, 'monto_pagado' => 0, 'estatus' => 'pendiente',
            'validacion_detalle' => ['cfdi_relacionados' => [self::UUID_FIN], 'tipo_relacion' => '07'],
        ]);
        $datos = [
            'uuid' => 'DD856DAF-954C-40E9-A2FA-C8A17FE86A4D', 'total' => self::MONTO,
            'forma_pago' => '30', 'es_nota_credito' => true,
            'cfdi_relacionados' => [self::UUID_FIN], 'tipo_relacion' => '07',
            'conceptos_detalle' => [['clave_prod_serv' => '84111506']],
        ];
        $this->invocar('procesarFlujoAnticipos', [$f, $datos, self::COD]);

        return $f;
    }

    public function test_al_subir_anticipo_se_registra_en_anticipos_proveedor(): void
    {
        $this->subirAnticipo();

        $ant = AnticipoProveedor::where('uuid_cfdi', self::UUID_ANT)->first();
        $this->assertNotNull($ant, 'El anticipo del portal debe registrarse en anticipos_proveedor');
        $this->assertSame('pagado', $ant->estatus);
        $this->assertEqualsWithDelta(self::MONTO, (float) $ant->total_banco, 0.01);
        $this->assertEqualsWithDelta(0, (float) $ant->monto_aplicado, 0.01);
    }

    public function test_orden_normal_anticipo_luego_factura_aplica_el_anticipo(): void
    {
        $this->subirAnticipo();
        $this->subirFacturaFinal();

        $fin = Factura::where('uuid_cfdi', self::UUID_FIN)->first();
        $this->assertEqualsWithDelta(self::MONTO, (float) $fin->monto_pagado, 0.01, 'La factura final debe quedar cubierta por el anticipo');
        $this->assertSame('pagada', $fin->estatus);

        $ant = AnticipoProveedor::where('uuid_cfdi', self::UUID_ANT)->first();
        $this->assertSame('aplicado', $ant->estatus);
        $this->assertEqualsWithDelta(self::MONTO, (float) $ant->monto_aplicado, 0.01);
    }

    public function test_orden_invertido_factura_primero_luego_anticipo_tambien_liga(): void
    {
        // La factura final llega ANTES que el anticipo.
        $this->subirFacturaFinal();
        // En este punto la factura aún no tiene anticipo -> sigue pendiente.
        $fin = Factura::where('uuid_cfdi', self::UUID_FIN)->first();
        $this->assertSame('pendiente', $fin->estatus);
        $this->assertEqualsWithDelta(0, (float) $fin->monto_pagado, 0.01);

        // Llega el anticipo -> debe reconciliar hacia atrás y cubrir la factura.
        $this->subirAnticipo();

        $fin->refresh();
        $this->assertEqualsWithDelta(self::MONTO, (float) $fin->monto_pagado, 0.01, 'Al llegar el anticipo debe aplicarse a la factura final previa');
        $this->assertSame('pagada', $fin->estatus);
    }

    public function test_nc_forma_30_mata_el_anticipo(): void
    {
        $this->subirAnticipo();
        // La NC relaciona la factura final; sin la factura, usa el puente. Subimos la factura final.
        $this->subirFacturaFinal();
        $this->subirNotaCredito();

        $ant = AnticipoProveedor::where('uuid_cfdi', self::UUID_ANT)->first();
        $this->assertSame('aplicado', $ant->estatus, 'La NC forma 30 debe dejar el anticipo como aplicado/muerto');
        $this->assertEqualsWithDelta((float) $ant->total_banco, (float) $ant->monto_aplicado, 0.01, 'El saldo del anticipo debe quedar en 0');
        $this->assertTrue((bool) ($ant->datos['matado_por_nc'] ?? false));
    }

    public function test_no_se_duplica_el_dinero_si_se_reprocesa(): void
    {
        $this->subirAnticipo();
        $this->subirFacturaFinal();

        // Reprocesar la factura final NO debe volver a aplicar el anticipo (guarda anti-doble).
        $fin = Factura::where('uuid_cfdi', self::UUID_FIN)->first();
        $datos = [
            'uuid' => self::UUID_FIN, 'total' => self::MONTO, 'serie' => 'INV', 'folio' => '2345',
            'conceptos_detalle' => [['clave_prod_serv' => '12161900']],
            'cfdi_relacionados' => [self::UUID_ANT], 'tipo_relacion' => '07', 'es_nota_credito' => false,
        ];
        $this->invocar('reconciliarAnticipos', [$fin, $datos, self::COD, false]);

        $fin->refresh();
        $this->assertEqualsWithDelta(self::MONTO, (float) $fin->monto_pagado, 0.01, 'No debe pagar más que el total aunque se reprocese');
    }
}
