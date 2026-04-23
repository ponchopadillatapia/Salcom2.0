<?php

namespace Database\Seeders;

use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // ── Productos ──
        $productos = [
            ['codigo' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'descripcion' => 'Resina de alta viscosidad para uso industrial', 'categoria' => 'Materia prima', 'precio' => 85.00, 'unidad_venta' => 'kg', 'stock' => 1200, 'activo' => true],
            ['codigo' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'descripcion' => 'Solvente de alta pureza', 'categoria' => 'Materia prima', 'precio' => 42.50, 'unidad_venta' => 'lt', 'stock' => 150, 'activo' => true],
            ['codigo' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'descripcion' => 'Pigmento ecológico base agua', 'categoria' => 'Materia prima', 'precio' => 120.00, 'unidad_venta' => 'kg', 'stock' => 300, 'activo' => true],
            ['codigo' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'descripcion' => 'Catalizador de curado rápido', 'categoria' => 'Consumible', 'precio' => 210.00, 'unidad_venta' => 'kg', 'stock' => 25, 'activo' => true],
            ['codigo' => 'SAL-009', 'nombre' => 'Aditivo antioxidante', 'descripcion' => 'Aditivo para prevenir oxidación', 'categoria' => 'Consumible', 'precio' => 55.00, 'unidad_venta' => 'kg', 'stock' => 500, 'activo' => true],
            ['codigo' => 'SAL-011', 'nombre' => 'Fibra de refuerzo', 'descripcion' => 'Fibra de vidrio para refuerzo estructural', 'categoria' => 'Materia prima', 'precio' => 320.00, 'unidad_venta' => 'rollo', 'stock' => 80, 'activo' => true],
            ['codigo' => 'SAL-013', 'nombre' => 'Adhesivo estructural', 'descripcion' => 'Adhesivo de alta resistencia', 'categoria' => 'Producto terminado', 'precio' => 180.00, 'unidad_venta' => 'kg', 'stock' => 0, 'activo' => true],
            ['codigo' => 'SAL-015', 'nombre' => 'Sellador industrial', 'descripcion' => 'Sellador para juntas industriales', 'categoria' => 'Producto terminado', 'precio' => 95.00, 'unidad_venta' => 'lt', 'stock' => 45, 'activo' => true],
        ];

        foreach ($productos as $p) {
            Producto::updateOrCreate(['codigo' => $p['codigo']], $p);
        }

        // ── Pedidos (últimos 6 meses) ──
        $pedidos = [
            ['folio' => 'PED-2025-089', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 500, 'precio' => 85]], 'total' => 42500, 'tipo_pago' => 'credito', 'estatus' => 'entregado', 'created_at' => '2025-11-15'],
            ['folio' => 'PED-2025-102', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-003', 'nombre' => 'Solvente técnico', 'cantidad' => 200, 'precio' => 42.5]], 'total' => 8500, 'tipo_pago' => 'contado', 'estatus' => 'entregado', 'created_at' => '2025-12-03'],
            ['folio' => 'PED-2026-008', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 600, 'precio' => 85], ['sku' => 'SAL-005', 'nombre' => 'Pigmento', 'cantidad' => 100, 'precio' => 120]], 'total' => 63000, 'tipo_pago' => 'credito', 'estatus' => 'entregado', 'created_at' => '2026-01-10'],
            ['folio' => 'PED-2026-021', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-007', 'nombre' => 'Catalizador', 'cantidad' => 50, 'precio' => 210]], 'total' => 10500, 'tipo_pago' => 'contado', 'estatus' => 'entregado', 'created_at' => '2026-02-05'],
            ['folio' => 'PED-2026-035', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 750, 'precio' => 85], ['sku' => 'SAL-003', 'nombre' => 'Solvente', 'cantidad' => 300, 'precio' => 42.5]], 'total' => 76500, 'tipo_pago' => 'credito', 'estatus' => 'enviado', 'created_at' => '2026-03-12'],
            ['folio' => 'PED-2026-048', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 800, 'precio' => 85]], 'total' => 68000, 'tipo_pago' => 'credito', 'estatus' => 'procesando', 'created_at' => '2026-04-02'],
            ['folio' => 'PED-2026-055', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-005', 'nombre' => 'Pigmento', 'cantidad' => 80, 'precio' => 120]], 'total' => 9600, 'tipo_pago' => 'contado', 'estatus' => 'validacion', 'created_at' => '2026-04-15'],
        ];

        foreach ($pedidos as $p) {
            Pedido::updateOrCreate(['folio' => $p['folio']], $p);
        }

        // ── Facturas ──
        $facturas = [
            ['folio_cfdi' => 'CFDI-A-001230', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 36206.90, 'monto_iva' => 5793.10, 'total' => 42000, 'estatus' => 'pagada', 'fecha_vencimiento' => '2025-12-15'],
            ['folio_cfdi' => 'CFDI-A-001231', 'codigo_cliente' => 'CLI-2026-002', 'monto' => 7327.59, 'monto_iva' => 1172.41, 'total' => 8500, 'estatus' => 'pagada', 'fecha_vencimiento' => '2026-01-03'],
            ['folio_cfdi' => 'CFDI-A-001235', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 54310.34, 'monto_iva' => 8689.66, 'total' => 63000, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-02-10'],
            ['folio_cfdi' => 'CFDI-A-001236', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 65948.28, 'monto_iva' => 10551.72, 'total' => 76500, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-04-12'],
            ['folio_cfdi' => 'CFDI-P-000501', 'codigo_proveedor' => '102003240', 'monto' => 12500, 'monto_iva' => 2000, 'total' => 14500, 'estatus' => 'pagada', 'fecha_vencimiento' => '2026-03-01'],
            ['folio_cfdi' => 'CFDI-P-000502', 'codigo_proveedor' => '102003241', 'monto' => 8200, 'monto_iva' => 1312, 'total' => 9512, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-04-20'],
        ];

        foreach ($facturas as $f) {
            Factura::updateOrCreate(['folio_cfdi' => $f['folio_cfdi']], $f);
        }

        // ── Encuestas ──
        $encuestas = [
            ['codigo_cliente' => 'CLI-2026-001', 'calificacion' => 5, 'tiempo_entrega' => 5, 'calidad_producto' => 5, 'comentarios' => 'Excelente servicio y calidad'],
            ['codigo_cliente' => 'CLI-2026-001', 'calificacion' => 4, 'tiempo_entrega' => 4, 'calidad_producto' => 5, 'comentarios' => 'Buen producto, entrega un poco lenta'],
            ['codigo_cliente' => 'CLI-2026-002', 'calificacion' => 4, 'tiempo_entrega' => 3, 'calidad_producto' => 4, 'comentarios' => 'Todo bien'],
            ['codigo_cliente' => 'CLI-2026-002', 'calificacion' => 3, 'tiempo_entrega' => 2, 'calidad_producto' => 4, 'comentarios' => 'La entrega tardó más de lo esperado'],
        ];

        foreach ($encuestas as $e) {
            Encuesta::create($e);
        }

        // ── Muestras ──
        $muestras = [
            ['lote' => 'LOTE-2026-001', 'producto' => 'Resina epóxica premium', 'proveedor' => 'Distribuidora Nacional SA de CV', 'descripcion' => 'Nueva formulación de resina', 'cantidad' => 5, 'unidad' => 'kg', 'etapa' => 'laboratorio', 'fecha_registro' => '2026-03-15', 'fecha_recepcion' => '2026-03-16', 'fecha_laboratorio' => '2026-03-20'],
            ['lote' => 'LOTE-2026-002', 'producto' => 'Solvente ecológico', 'proveedor' => 'Materiales Industriales del Bajío', 'descripcion' => 'Solvente base agua', 'cantidad' => 10, 'unidad' => 'lt', 'etapa' => 'piso', 'fecha_registro' => '2026-03-01', 'fecha_recepcion' => '2026-03-02', 'fecha_laboratorio' => '2026-03-05', 'fecha_piso' => '2026-03-20'],
            ['lote' => 'LOTE-2026-003', 'producto' => 'Pigmento orgánico', 'proveedor' => 'Juan Pérez López', 'descripcion' => 'Pigmento natural', 'cantidad' => 3, 'unidad' => 'kg', 'etapa' => 'registro', 'fecha_registro' => '2026-04-10'],
        ];

        foreach ($muestras as $m) {
            Muestra::updateOrCreate(['lote' => $m['lote']], $m);
        }

        // ── Score de proveedores ──
        \App\Models\ProveedorUser::where('usuario', 'PROV001')->update(['score_entrega' => 94, 'score_puntualidad' => 88, 'score_total' => 91]);
        \App\Models\ProveedorUser::where('usuario', 'PROV002')->update(['score_entrega' => 78, 'score_puntualidad' => 82, 'score_total' => 80]);
        \App\Models\ProveedorUser::where('usuario', 'PROV003')->update(['score_entrega' => 65, 'score_puntualidad' => 70, 'score_total' => 67.5]);
    }
}
