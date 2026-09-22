<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FacturasProveedorAislamientoTest extends TestCase
{
    use RefreshDatabase;

    private function crearProveedor(string $usuario, ?string $idProveedor = null): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario' => $usuario,
            'password' => Hash::make('secret123'),
            'nombre' => 'Proveedor '.$usuario,
            'id_proveedor' => $idProveedor,
            'correo' => strtolower($usuario).'@test.com',
            'tipo_persona' => 'Fisica',
            'telefono' => '5551234567',
            'activo' => true,
        ]);
    }

    private function crearFactura(string $codigoProveedor, string $folio): Factura
    {
        return Factura::create([
            'folio_cfdi' => $folio,
            'uuid_cfdi' => strtoupper(uniqid('uuid-')),
            'codigo_proveedor' => $codigoProveedor,
            'monto' => 100,
            'monto_iva' => 16,
            'total' => 116,
            'estatus' => 'pendiente',
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'dias_plazo' => 30,
        ]);
    }

    public function test_proveedor_nuevo_sin_id_solo_ve_sus_facturas_en_alta(): void
    {
        $nuevo = $this->crearProveedor('NUEVO1', null);
        $otro = $this->crearProveedor('OTRO1', 'WIESE-999');

        $this->crearFactura('WIESE-999', 'F-OTRO-1');
        $this->crearFactura('P'.$nuevo->id, 'F-MIO-1');

        $response = $this->withSession([
            'proveedor_id' => $nuevo->id,
            'proveedor_codigo' => null,
        ])->get(route('proveedores.fiscal'));

        $response->assertOk();
        $response->assertSee('F-MIO-1');
        $response->assertDontSee('F-OTRO-1');
    }

    public function test_proveedor_nuevo_sin_id_solo_ve_sus_facturas_en_listado(): void
    {
        $nuevo = $this->crearProveedor('NUEVO2', null);
        $otro = $this->crearProveedor('OTRO2', 'WIESE-888');

        $this->crearFactura('WIESE-888', 'F-OTRO-2');
        $this->crearFactura('P'.$nuevo->id, 'F-MIO-2');

        $response = $this->withSession([
            'proveedor_id' => $nuevo->id,
            'proveedor_codigo' => null,
        ])->get(route('proveedores.facturas'));

        $response->assertOk();
        $response->assertSee('F-MIO-2');
        $response->assertDontSee('F-OTRO-2');
    }

    public function test_codigo_para_facturas_usa_fallback_p_id(): void
    {
        $proveedor = $this->crearProveedor('NUEVO3', null);

        $this->assertSame('P'.$proveedor->id, $proveedor->codigoParaFacturas());
        $this->assertContains('P'.$proveedor->id, $proveedor->codigosParaFacturas());
        $this->assertNotEmpty($proveedor->codigosParaFacturas());
    }
}
