<?php

namespace Database\Seeders;

use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\Notificacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\TrackingPedido;
use Illuminate\Database\Seeder;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════
        // PRODUCTOS (8)
        // ══════════════════════════════════════
        $productos = [
            ['codigo' => 'SAL-001', 'nombre' => 'Resina epóxica industrial',   'descripcion' => 'Resina de alta viscosidad para uso industrial',     'categoria' => 'Materia prima',       'precio' => 85.00,  'unidad_venta' => 'kg',    'stock' => 1200, 'activo' => true],
            ['codigo' => 'SAL-003', 'nombre' => 'Solvente grado técnico',      'descripcion' => 'Solvente de alta pureza',                           'categoria' => 'Materia prima',       'precio' => 42.50,  'unidad_venta' => 'lt',    'stock' => 150,  'activo' => true],
            ['codigo' => 'SAL-005', 'nombre' => 'Pigmento base agua',          'descripcion' => 'Pigmento ecológico base agua',                      'categoria' => 'Materia prima',       'precio' => 120.00, 'unidad_venta' => 'kg',    'stock' => 300,  'activo' => true],
            ['codigo' => 'SAL-007', 'nombre' => 'Catalizador rápido',          'descripcion' => 'Catalizador de curado rápido',                      'categoria' => 'Consumible',          'precio' => 210.00, 'unidad_venta' => 'kg',    'stock' => 25,   'activo' => true],
            ['codigo' => 'SAL-009', 'nombre' => 'Aditivo antioxidante',        'descripcion' => 'Aditivo para prevenir oxidación',                   'categoria' => 'Consumible',          'precio' => 55.00,  'unidad_venta' => 'kg',    'stock' => 500,  'activo' => true],
            ['codigo' => 'SAL-011', 'nombre' => 'Fibra de refuerzo',           'descripcion' => 'Fibra de vidrio para refuerzo estructural',         'categoria' => 'Materia prima',       'precio' => 320.00, 'unidad_venta' => 'rollo', 'stock' => 80,   'activo' => true],
            ['codigo' => 'SAL-013', 'nombre' => 'Adhesivo estructural',        'descripcion' => 'Adhesivo de alta resistencia',                      'categoria' => 'Producto terminado',  'precio' => 180.00, 'unidad_venta' => 'kg',    'stock' => 0,    'activo' => true],
            ['codigo' => 'SAL-015', 'nombre' => 'Sellador industrial',         'descripcion' => 'Sellador para juntas industriales',                 'categoria' => 'Producto terminado',  'precio' => 95.00,  'unidad_venta' => 'lt',    'stock' => 45,   'activo' => true],
        ];

        foreach ($productos as $p) {
            Producto::updateOrCreate(['codigo' => $p['codigo']], $p);
        }

        // ══════════════════════════════════════
        // PEDIDOS (10 — variados en estatus y fechas)
        // ══════════════════════════════════════
        $pedidos = [
            ['folio' => 'PED-2025-089', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 500, 'precio' => 85]], 'total' => 42500.00, 'tipo_pago' => 'credito', 'estatus' => 'entregado', 'notas' => 'Entregado sin novedad', 'created_at' => '2025-11-15'],
            ['folio' => 'PED-2025-102', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-003', 'nombre' => 'Solvente técnico', 'cantidad' => 200, 'precio' => 42.5]], 'total' => 8500.00, 'tipo_pago' => 'contado', 'estatus' => 'entregado', 'notas' => null, 'created_at' => '2025-12-03'],
            ['folio' => 'PED-2026-008', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 600, 'precio' => 85], ['sku' => 'SAL-005', 'nombre' => 'Pigmento', 'cantidad' => 100, 'precio' => 120]], 'total' => 63000.00, 'tipo_pago' => 'credito', 'estatus' => 'entregado', 'notas' => null, 'created_at' => '2026-01-10'],
            ['folio' => 'PED-2026-021', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-007', 'nombre' => 'Catalizador', 'cantidad' => 50, 'precio' => 210]], 'total' => 10500.00, 'tipo_pago' => 'contado', 'estatus' => 'entregado', 'notas' => 'Cliente recogió en planta', 'created_at' => '2026-02-05'],
            ['folio' => 'PED-2026-035', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 750, 'precio' => 85], ['sku' => 'SAL-003', 'nombre' => 'Solvente', 'cantidad' => 300, 'precio' => 42.5]], 'total' => 76500.00, 'tipo_pago' => 'credito', 'estatus' => 'enviado', 'notas' => 'Guía Estafeta: 6024958372615', 'created_at' => '2026-03-12'],
            ['folio' => 'PED-2026-048', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-001', 'nombre' => 'Resina epóxica', 'cantidad' => 800, 'precio' => 85]], 'total' => 68000.00, 'tipo_pago' => 'credito', 'estatus' => 'procesando', 'notas' => 'En producción lote #4521', 'created_at' => '2026-04-02'],
            ['folio' => 'PED-2026-055', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-005', 'nombre' => 'Pigmento', 'cantidad' => 80, 'precio' => 120]], 'total' => 9600.00, 'tipo_pago' => 'contado', 'estatus' => 'validacion', 'notas' => 'Pendiente verificar stock', 'created_at' => '2026-04-15'],
            ['folio' => 'PED-2026-061', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-011', 'nombre' => 'Fibra de refuerzo', 'cantidad' => 20, 'precio' => 320], ['sku' => 'SAL-009', 'nombre' => 'Aditivo antioxidante', 'cantidad' => 100, 'precio' => 55]], 'total' => 11900.00, 'tipo_pago' => 'credito', 'estatus' => 'procesando', 'notas' => null, 'created_at' => '2026-04-20'],
            ['folio' => 'PED-2026-068', 'codigo_cliente' => 'CLI-2026-002', 'nombre_cliente' => 'Ferretería López', 'productos' => [['sku' => 'SAL-015', 'nombre' => 'Sellador industrial', 'cantidad' => 30, 'precio' => 95]], 'total' => 2850.00, 'tipo_pago' => 'contado', 'estatus' => 'validacion', 'notas' => null, 'created_at' => '2026-04-25'],
            ['folio' => 'PED-2025-075', 'codigo_cliente' => 'CLI-2026-001', 'nombre_cliente' => 'Comercializadora del Norte SA de CV', 'productos' => [['sku' => 'SAL-013', 'nombre' => 'Adhesivo estructural', 'cantidad' => 40, 'precio' => 180]], 'total' => 7200.00, 'tipo_pago' => 'credito', 'estatus' => 'cancelado', 'notas' => 'Cancelado por cliente — cambio de especificación', 'created_at' => '2025-10-20'],
        ];

        foreach ($pedidos as $p) {
            Pedido::updateOrCreate(['folio' => $p['folio']], $p);
        }

        // ══════════════════════════════════════
        // TRACKING DE PEDIDOS
        // ══════════════════════════════════════
        $pedidoEnviado = Pedido::where('folio', 'PED-2026-035')->first();
        if ($pedidoEnviado) {
            $trackings = [
                ['pedido_id' => $pedidoEnviado->id, 'estatus' => 'validacion',  'descripcion' => 'Pedido recibido y en validación',           'fecha' => '2026-03-12 09:00:00', 'usuario_responsable' => 'Sistema'],
                ['pedido_id' => $pedidoEnviado->id, 'estatus' => 'procesando',  'descripcion' => 'Pedido aprobado, en producción',             'fecha' => '2026-03-13 11:30:00', 'usuario_responsable' => 'ADMIN001'],
                ['pedido_id' => $pedidoEnviado->id, 'estatus' => 'enviado',     'descripcion' => 'Enviado vía Estafeta — Guía: 6024958372615', 'fecha' => '2026-03-18 16:00:00', 'usuario_responsable' => 'ADMIN001'],
            ];
            foreach ($trackings as $t) {
                TrackingPedido::updateOrCreate(
                    ['pedido_id' => $t['pedido_id'], 'estatus' => $t['estatus']],
                    $t
                );
            }
        }

        $pedidoProcesando = Pedido::where('folio', 'PED-2026-048')->first();
        if ($pedidoProcesando) {
            $trackings2 = [
                ['pedido_id' => $pedidoProcesando->id, 'estatus' => 'validacion', 'descripcion' => 'Pedido recibido',                    'fecha' => '2026-04-02 10:00:00', 'usuario_responsable' => 'Sistema'],
                ['pedido_id' => $pedidoProcesando->id, 'estatus' => 'procesando', 'descripcion' => 'En producción — lote #4521',         'fecha' => '2026-04-03 08:15:00', 'usuario_responsable' => 'ADMIN001'],
            ];
            foreach ($trackings2 as $t) {
                TrackingPedido::updateOrCreate(
                    ['pedido_id' => $t['pedido_id'], 'estatus' => $t['estatus']],
                    $t
                );
            }
        }

        // ══════════════════════════════════════
        // FACTURAS (8 — mix de pagadas, pendientes y vencidas)
        // ══════════════════════════════════════
        $facturas = [
            ['folio_cfdi' => 'CFDI-A-001230', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 36206.90, 'monto_iva' => 5793.10, 'total' => 42000.00, 'estatus' => 'pagada',    'fecha_vencimiento' => '2025-12-15'],
            ['folio_cfdi' => 'CFDI-A-001231', 'codigo_cliente' => 'CLI-2026-002', 'monto' => 7327.59,  'monto_iva' => 1172.41, 'total' => 8500.00,  'estatus' => 'pagada',    'fecha_vencimiento' => '2026-01-03'],
            ['folio_cfdi' => 'CFDI-A-001235', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 54310.34, 'monto_iva' => 8689.66, 'total' => 63000.00, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-02-10'],
            ['folio_cfdi' => 'CFDI-A-001236', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 65948.28, 'monto_iva' => 10551.72,'total' => 76500.00, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-04-12'],
            ['folio_cfdi' => 'CFDI-A-001240', 'codigo_cliente' => 'CLI-2026-001', 'monto' => 58620.69, 'monto_iva' => 9379.31, 'total' => 68000.00, 'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-05-02'],
            ['folio_cfdi' => 'CFDI-A-001241', 'codigo_cliente' => 'CLI-2026-002', 'monto' => 8275.86,  'monto_iva' => 1324.14, 'total' => 9600.00,  'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-05-15'],
            ['folio_cfdi' => 'CFDI-P-000501', 'codigo_proveedor' => '102003240',  'monto' => 12500.00, 'monto_iva' => 2000.00, 'total' => 14500.00, 'estatus' => 'pagada',    'fecha_vencimiento' => '2026-03-01'],
            ['folio_cfdi' => 'CFDI-P-000502', 'codigo_proveedor' => '102003241',  'monto' => 8200.00,  'monto_iva' => 1312.00, 'total' => 9512.00,  'estatus' => 'pendiente', 'fecha_vencimiento' => '2026-04-20'],
        ];

        foreach ($facturas as $f) {
            Factura::updateOrCreate(['folio_cfdi' => $f['folio_cfdi']], $f);
        }

        // ══════════════════════════════════════
        // ENCUESTAS (6 — variadas)
        // ══════════════════════════════════════
        Encuesta::query()->delete(); // limpiar duplicados
        $encuestas = [
            ['codigo_cliente' => 'CLI-2026-001', 'calificacion' => 5, 'tiempo_entrega' => 5, 'calidad_producto' => 5, 'comentarios' => 'Excelente servicio y calidad, siempre puntuales'],
            ['codigo_cliente' => 'CLI-2026-001', 'calificacion' => 4, 'tiempo_entrega' => 4, 'calidad_producto' => 5, 'comentarios' => 'Buen producto, entrega un poco lenta esta vez'],
            ['codigo_cliente' => 'CLI-2026-001', 'calificacion' => 5, 'tiempo_entrega' => 5, 'calidad_producto' => 4, 'comentarios' => 'Muy satisfecho con el servicio'],
            ['codigo_cliente' => 'CLI-2026-002', 'calificacion' => 4, 'tiempo_entrega' => 3, 'calidad_producto' => 4, 'comentarios' => 'Todo bien, pero la entrega tardó más de lo esperado'],
            ['codigo_cliente' => 'CLI-2026-002', 'calificacion' => 3, 'tiempo_entrega' => 2, 'calidad_producto' => 4, 'comentarios' => 'La entrega tardó demasiado, producto OK'],
            ['codigo_cliente' => 'CLI-2026-002', 'calificacion' => 5, 'tiempo_entrega' => 5, 'calidad_producto' => 5, 'comentarios' => 'Perfecto, nada que mejorar'],
        ];

        foreach ($encuestas as $e) {
            Encuesta::create($e);
        }

        // ══════════════════════════════════════
        // MUESTRAS (4 — en distintas etapas)
        // ══════════════════════════════════════
        $muestras = [
            ['lote' => 'LOTE-2026-001', 'producto' => 'Resina epóxica premium',  'proveedor' => 'Distribuidora Nacional SA de CV',     'descripcion' => 'Nueva formulación de resina con mayor resistencia', 'cantidad' => 5,  'unidad' => 'kg', 'etapa' => 'laboratorio',  'fecha_registro' => '2026-03-15', 'fecha_recepcion' => '2026-03-16', 'fecha_laboratorio' => '2026-03-20'],
            ['lote' => 'LOTE-2026-002', 'producto' => 'Solvente ecológico',      'proveedor' => 'Materiales Industriales del Bajío',   'descripcion' => 'Solvente base agua biodegradable',                  'cantidad' => 10, 'unidad' => 'lt', 'etapa' => 'piso',         'fecha_registro' => '2026-03-01', 'fecha_recepcion' => '2026-03-02', 'fecha_laboratorio' => '2026-03-05', 'fecha_piso' => '2026-03-20'],
            ['lote' => 'LOTE-2026-003', 'producto' => 'Pigmento orgánico',       'proveedor' => 'Juan Pérez López',                    'descripcion' => 'Pigmento natural para recubrimientos',              'cantidad' => 3,  'unidad' => 'kg', 'etapa' => 'registro',     'fecha_registro' => '2026-04-10'],
            ['lote' => 'LOTE-2026-004', 'producto' => 'Catalizador UV',          'proveedor' => 'Distribuidora Nacional SA de CV',     'descripcion' => 'Catalizador de curado por luz UV',                  'cantidad' => 2,  'unidad' => 'kg', 'etapa' => 'estabilidad',  'fecha_registro' => '2026-02-10', 'fecha_recepcion' => '2026-02-11', 'fecha_laboratorio' => '2026-02-15', 'fecha_piso' => '2026-03-05', 'fecha_estabilidad' => '2026-03-15'],
        ];

        foreach ($muestras as $m) {
            Muestra::updateOrCreate(['lote' => $m['lote']], $m);
        }

        // ══════════════════════════════════════
        // DOCUMENTOS DE PROVEEDORES (6 — mix de estatus)
        // ══════════════════════════════════════
        $prov1 = \App\Models\ProveedorUser::where('usuario', 'PROV001')->first();
        $prov2 = \App\Models\ProveedorUser::where('usuario', 'PROV002')->first();
        $prov3 = \App\Models\ProveedorUser::where('usuario', 'PROV003')->first();

        if ($prov1) {
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov1->id, 'tipo' => 'cif'],
                ['archivo' => 'cif/prov001_cif.pdf', 'estatus' => 'aprobado', 'notas_revision' => 'RFC válido, documento vigente', 'revisado_at' => '2026-03-20']
            );
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov1->id, 'tipo' => 'opinion'],
                ['archivo' => 'opiniones/prov001_opinion.pdf', 'estatus' => 'aprobado', 'notas_revision' => 'Opinión positiva abril 2026', 'revisado_at' => '2026-04-05']
            );
        }
        if ($prov2) {
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov2->id, 'tipo' => 'cif'],
                ['archivo' => 'cif/prov002_cif.pdf', 'estatus' => 'pendiente']
            );
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov2->id, 'tipo' => 'caratula_banco'],
                ['archivo' => 'caratula_banco/prov002_banco.pdf', 'estatus' => 'pendiente']
            );
        }
        if ($prov3) {
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov3->id, 'tipo' => 'cif'],
                ['archivo' => 'cif/prov003_cif.pdf', 'estatus' => 'rechazado', 'notas_revision' => 'RFC no coincide con nombre', 'revisado_at' => '2026-04-10']
            );
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $prov3->id, 'tipo' => 'opinion'],
                ['archivo' => 'opiniones/prov003_opinion.pdf', 'estatus' => 'pendiente']
            );
        }

        // ══════════════════════════════════════
        // NOTIFICACIONES (5)
        // ══════════════════════════════════════
        $notificaciones = [
            ['tipo_usuario' => 'cliente', 'codigo_usuario' => 'CLI-2026-001', 'titulo' => 'Pedido PED-2026-035 — Enviado',     'mensaje' => 'Tu pedido PED-2026-035 ha sido enviado vía Estafeta.',                  'leida' => false, 'tipo' => 'pedido_estatus'],
            ['tipo_usuario' => 'cliente', 'codigo_usuario' => 'CLI-2026-001', 'titulo' => 'Pedido PED-2026-048 — Procesando',  'mensaje' => 'Tu pedido PED-2026-048 está en producción.',                             'leida' => false, 'tipo' => 'pedido_estatus'],
            ['tipo_usuario' => 'cliente', 'codigo_usuario' => 'CLI-2026-001', 'titulo' => 'Factura CFDI-A-001235 vencida',      'mensaje' => 'Tu factura CFDI-A-001235 por $63,000 venció el 10/02/2026.',             'leida' => true,  'tipo' => 'factura'],
            ['tipo_usuario' => 'cliente', 'codigo_usuario' => 'CLI-2026-002', 'titulo' => 'Pedido PED-2026-055 — En validación','mensaje' => 'Tu pedido PED-2026-055 está siendo validado.',                           'leida' => false, 'tipo' => 'pedido_estatus'],
            ['tipo_usuario' => 'proveedor','codigo_usuario' => '102003241',   'titulo' => 'Documento pendiente',                'mensaje' => 'Tu CIF está pendiente de revisión. Sube el documento actualizado.',     'leida' => false, 'tipo' => 'documento'],
        ];

        foreach ($notificaciones as $n) {
            Notificacion::create($n);
        }

        // ══════════════════════════════════════
        // SCORES DE PROVEEDORES
        // ══════════════════════════════════════
        \App\Models\ProveedorUser::where('usuario', 'PROV001')->update(['score_entrega' => 94, 'score_puntualidad' => 88, 'score_total' => 91]);
        \App\Models\ProveedorUser::where('usuario', 'PROV002')->update(['score_entrega' => 78, 'score_puntualidad' => 82, 'score_total' => 80]);
        \App\Models\ProveedorUser::where('usuario', 'PROV003')->update(['score_entrega' => 65, 'score_puntualidad' => 70, 'score_total' => 67.5]);
    }
}
