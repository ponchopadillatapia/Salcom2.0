<?php

namespace Tests\Feature;

use App\Http\Controllers\PortalProveedorController;
use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\ProveedorApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * "WIESE MANDA": el estatus que se muestra de una factura local NUNCA debe contradecir a Wiese.
 * Si Wiese dice pendiente/cancelada, se pisa el estatus local mostrado con el de Wiese.
 *
 * Verifica el helper Factura::estatusDesdeWiese y el reconciliador
 * PortalProveedorController::alinearEstatusFacturasConWiese (vía reflexión).
 */
class EstatusWieseMandaTest extends TestCase
{
    use RefreshDatabase;

    private const COD = 'M213008002';

    public function test_helper_estatus_desde_wiese(): void
    {
        $this->assertSame('cancelada', Factura::estatusDesdeWiese(['ccancelado' => 1, 'cpendiente' => 500]));
        $this->assertSame('pendiente', Factura::estatusDesdeWiese(['ccancelado' => 0, 'cpendiente' => 500]));
        $this->assertSame('pagada', Factura::estatusDesdeWiese(['ccancelado' => 0, 'cpendiente' => 0]));
        $this->assertSame('pendiente', Factura::estatusDesdeWiese(['saldo' => 100]));
        $this->assertSame('pagada', Factura::estatusDesdeWiese(['saldo' => 0]));
    }

    public function test_wiese_manda_pisa_el_estatus_local_mostrado(): void
    {
        $prov = ProveedorUser::create([
            'usuario' => 'harmony.test', 'password' => bcrypt('x'),
            'nombre' => 'HARMONY CHEM', 'id_proveedor' => self::COD, 'activo' => true,
        ]);

        // Factura LOCAL marcada 'programada' por el flujo interno.
        $f = Factura::create([
            'folio_cfdi' => 'ALMPA2830', 'uuid_cfdi' => 'UUID-TEST-1', 'codigo_proveedor' => self::COD,
            'monto' => 900000, 'monto_iva' => 150000, 'total' => 1051922.80, 'monto_pagado' => 0,
            'estatus' => 'programada',
            'validacion_detalle' => ['serie' => 'ALMPA', 'folio' => '2830'],
        ]);

        // Wiese dice que ESA factura sigue PENDIENTE (tiene saldo). Mockeamos el API.
        $mock = Mockery::mock(ProveedorApiService::class);
        $mock->shouldReceive('listarFacturasPendientesTodos')->andReturn([
            'success' => true,
            'data' => ['items' => [[
                'codigo' => self::COD, 'serie' => 'ALMPA', 'folio' => '2830',
                'saldo' => 1051922.80, 'cpendiente' => 1051922.80, 'ccancelado' => 0,
                'fecha_vence' => '2026-10-31T00:00:00',
            ]]],
        ]);
        $this->app->instance(ProveedorApiService::class, $mock);

        // Ejecutar el reconciliador sobre la colección.
        $c = app(PortalProveedorController::class);
        $m = new ReflectionMethod($c, 'alinearEstatusFacturasConWiese');
        $m->setAccessible(true);
        $coleccion = new Collection([$f]);
        $m->invokeArgs($c, [$coleccion, $prov]);

        // El estatus MOSTRADO debe pasar a 'pendiente' (lo que dice Wiese), NO 'programada' local.
        $this->assertSame('pendiente', $f->estatus, 'Wiese manda: el estatus local debe pisarse con el de Wiese');
    }

    public function test_si_wiese_no_la_reporta_se_deja_el_estatus_local(): void
    {
        $prov = ProveedorUser::create([
            'usuario' => 'harmony.test2', 'password' => bcrypt('x'),
            'nombre' => 'HARMONY CHEM', 'id_proveedor' => self::COD, 'activo' => true,
        ]);
        $f = Factura::create([
            'folio_cfdi' => 'XXX999', 'uuid_cfdi' => 'UUID-TEST-2', 'codigo_proveedor' => self::COD,
            'monto' => 100, 'monto_iva' => 16, 'total' => 116, 'monto_pagado' => 0,
            'estatus' => 'programada',
            'validacion_detalle' => ['serie' => 'XXX', 'folio' => '999'],
        ]);

        // Wiese NO reporta esta factura (lista vacía para el proveedor).
        $mock = Mockery::mock(ProveedorApiService::class);
        $mock->shouldReceive('listarFacturasPendientesTodos')->andReturn([
            'success' => true, 'data' => ['items' => []],
        ]);
        $this->app->instance(ProveedorApiService::class, $mock);

        $c = app(PortalProveedorController::class);
        $m = new ReflectionMethod($c, 'alinearEstatusFacturasConWiese');
        $m->setAccessible(true);
        $m->invokeArgs($c, [new Collection([$f]), $prov]);

        // No está en Wiese -> NO se inventa 'pagada'; se respeta el estatus local.
        $this->assertSame('programada', $f->estatus, 'Sin contraparte en Wiese, se conserva el estatus local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
