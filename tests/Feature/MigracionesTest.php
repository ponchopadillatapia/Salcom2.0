<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigracionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabla_pedidos_existe(): void
    {
        $this->assertTrue(Schema::hasTable('pedidos'));
        $this->assertTrue(Schema::hasColumns('pedidos', ['id', 'folio', 'codigo_cliente', 'nombre_cliente', 'productos', 'total', 'tipo_pago', 'estatus', 'notas', 'deleted_at']));
    }

    public function test_tabla_productos_existe(): void
    {
        $this->assertTrue(Schema::hasTable('productos'));
        $this->assertTrue(Schema::hasColumns('productos', ['id', 'codigo', 'nombre', 'descripcion', 'categoria', 'precio', 'unidad_venta', 'stock', 'activo', 'deleted_at']));
    }

    public function test_tabla_facturas_existe(): void
    {
        $this->assertTrue(Schema::hasTable('facturas'));
        $this->assertTrue(Schema::hasColumns('facturas', ['id', 'folio_cfdi', 'codigo_cliente', 'codigo_proveedor', 'pedido_id', 'monto', 'monto_iva', 'total', 'estatus', 'fecha_vencimiento', 'deleted_at']));
    }

    public function test_tabla_notificaciones_existe(): void
    {
        $this->assertTrue(Schema::hasTable('notificaciones'));
        $this->assertTrue(Schema::hasColumns('notificaciones', ['id', 'tipo_usuario', 'codigo_usuario', 'titulo', 'mensaje', 'leida', 'tipo']));
    }

    public function test_tabla_tracking_pedidos_existe(): void
    {
        $this->assertTrue(Schema::hasTable('tracking_pedidos'));
        $this->assertTrue(Schema::hasColumns('tracking_pedidos', ['id', 'pedido_id', 'estatus', 'descripcion', 'fecha', 'usuario_responsable']));
    }

    public function test_tabla_encuestas_existe(): void
    {
        $this->assertTrue(Schema::hasTable('encuestas'));
        $this->assertTrue(Schema::hasColumns('encuestas', ['id', 'codigo_cliente', 'pedido_id', 'calificacion', 'tiempo_entrega', 'calidad_producto', 'comentarios']));
    }

    public function test_modelos_pueden_crear_registros(): void
    {
        $pedido = \App\Models\Pedido::create([
            'folio' => 'PED-TEST-001', 'codigo_cliente' => 'CLI001', 'nombre_cliente' => 'Test',
            'productos' => [['codigo' => 'SAL-001', 'qty' => 5]], 'total' => 2425.00,
        ]);
        $this->assertDatabaseHas('pedidos', ['folio' => 'PED-TEST-001']);

        $producto = \App\Models\Producto::create([
            'codigo' => 'SAL-TEST', 'nombre' => 'Test Product', 'precio' => 100, 'unidad_venta' => 'Pieza',
        ]);
        $this->assertDatabaseHas('productos', ['codigo' => 'SAL-TEST']);

        $factura = \App\Models\Factura::create([
            'folio_cfdi' => 'CFDI-TEST-001', 'monto' => 100, 'monto_iva' => 16, 'total' => 116,
        ]);
        $this->assertDatabaseHas('facturas', ['folio_cfdi' => 'CFDI-TEST-001']);

        $notif = \App\Models\Notificacion::create([
            'tipo_usuario' => 'cliente', 'codigo_usuario' => 'CLI001', 'titulo' => 'Test', 'mensaje' => 'Msg',
        ]);
        $this->assertDatabaseHas('notificaciones', ['titulo' => 'Test']);

        $tracking = \App\Models\TrackingPedido::create([
            'pedido_id' => $pedido->id, 'estatus' => 'recibido', 'fecha' => now(),
        ]);
        $this->assertDatabaseHas('tracking_pedidos', ['estatus' => 'recibido']);

        $encuesta = \App\Models\Encuesta::create([
            'codigo_cliente' => 'CLI001', 'calificacion' => 5, 'tiempo_entrega' => 4, 'calidad_producto' => 5,
        ]);
        $this->assertDatabaseHas('encuestas', ['calificacion' => 5]);
    }
}
