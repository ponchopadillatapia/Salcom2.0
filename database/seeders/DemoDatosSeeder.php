<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\OcBorrador;
use App\Models\Producto;
use App\Models\ProveedorUser;
use Illuminate\Database\Seeder;

/**
 * Seeder para demo con jefes.
 * Pone datos variados en cada proveedor para mostrar todas las funciones:
 * - Scores OTIF diferentes
 * - Alertas y sugerencias de IA
 * - OC auto-generadas
 * - Productos con stock bajo para disparar IA
 */
class DemoDatosSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════
        // 1. SCORES OTIF VARIADOS
        // ══════════════════════════════════════
        // PROV001 — Excelente (proveedor preferente)
        ProveedorUser::where('usuario', 'PROV001')->update([
            'score_entrega' => 94,
            'score_puntualidad' => 88,
            'score_total' => 91,
        ]);

        // PROV002 — Aceptable (puede mejorar)
        ProveedorUser::where('usuario', 'PROV002')->update([
            'score_entrega' => 72,
            'score_puntualidad' => 68,
            'score_total' => 70,
        ]);

        // PROV003 — Bajo rendimiento (requiere atención)
        ProveedorUser::where('usuario', 'PROV003')->update([
            'score_entrega' => 45,
            'score_puntualidad' => 35,
            'score_total' => 40,
        ]);

        // Diego Cocca — Buen rendimiento
        ProveedorUser::where('usuario', 'diegococca@gmail.com')->update([
            'score_entrega' => 85,
            'score_puntualidad' => 78,
            'score_total' => 81.5,
        ]);

        // ══════════════════════════════════════
        // 2. PRODUCTOS CON STOCK VARIADO
        // ══════════════════════════════════════
        // Algunos con stock bajo para que la IA genere OC
        Producto::where('codigo', 'SAL-001')->update(['stock' => 5]);    // Casi agotado
        Producto::where('codigo', 'SAL-003')->update(['stock' => 150]);  // Normal
        Producto::where('codigo', 'SAL-005')->update(['stock' => 0]);    // Agotado
        Producto::where('codigo', 'SAL-007')->update(['stock' => 3]);    // Casi agotado
        Producto::where('codigo', 'SAL-009')->update(['stock' => 500]);  // OK
        Producto::where('codigo', 'SAL-011')->update(['stock' => 80]);   // Normal
        Producto::where('codigo', 'SAL-013')->update(['stock' => 0]);    // Agotado
        Producto::where('codigo', 'SAL-015')->update(['stock' => 45]);   // Normal

        // ══════════════════════════════════════
        // 3. LIMPIAR ALERTAS VIEJAS Y CREAR NUEVAS
        // ══════════════════════════════════════
        Alerta::query()->delete();

        $prov1 = ProveedorUser::where('usuario', 'PROV001')->first();
        $prov2 = ProveedorUser::where('usuario', 'PROV002')->first();
        $prov3 = ProveedorUser::where('usuario', 'PROV003')->first();
        $prov4 = ProveedorUser::where('usuario', 'diegococca@gmail.com')->first();

        // --- Sugerencias semanales (aparecen en Módulo IA) ---
        if ($prov1) {
            Alerta::create([
                'tipo' => 'sugerencia_ia',
                'modulo' => 'proveedores',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov1->id,
                'titulo' => 'Sugerencia semanal de Salcom IA',
                'contenido' => '¡Excelente rendimiento! Tu score es 91%. Mantén este nivel para seguir siendo proveedor preferente de Salcom. Recuerda revisar tus documentos fiscales en la sección Fiscal para mantenerlos al día.',
                'datos' => ['proveedor_id' => $prov1->id, 'tipo_sugerencia' => 'semanal'],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->startOfWeek()->addHours(8),
            ]);
        }

        if ($prov2) {
            Alerta::create([
                'tipo' => 'sugerencia_ia',
                'modulo' => 'proveedores',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov2->id,
                'titulo' => 'Sugerencia semanal de Salcom IA',
                'contenido' => 'Tu score actual es 70%. Para mejorarlo, enfócate en entregas a tiempo (actualmente en 68%). Tip: Confirma fechas de entrega con anticipación y avisa si hay retrasos. Un score arriba de 80% te da prioridad en nuevas OC.',
                'datos' => ['proveedor_id' => $prov2->id, 'tipo_sugerencia' => 'semanal'],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->startOfWeek()->addHours(8),
            ]);
        }

        if ($prov3) {
            Alerta::create([
                'tipo' => 'sugerencia_ia',
                'modulo' => 'proveedores',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov3->id,
                'titulo' => 'Sugerencia semanal de Salcom IA',
                'contenido' => 'Tu score actual es 40%. Para mejorarlo, enfócate en puntualidad (actualmente en 35%). Tip: Confirma fechas de entrega con anticipación y avisa si hay retrasos. Un score arriba de 80% te da prioridad en nuevas OC.',
                'datos' => ['proveedor_id' => $prov3->id, 'tipo_sugerencia' => 'semanal'],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->startOfWeek()->addHours(8),
            ]);

            // Alerta de bajo rendimiento para PROV003
            Alerta::create([
                'tipo' => 'proveedor_bajo_rendimiento',
                'modulo' => 'proveedores',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov3->id,
                'titulo' => 'Tu rendimiento está por debajo del umbral',
                'contenido' => 'Tu score OTIF de 40% está por debajo del mínimo requerido (60%). Si no mejora en las próximas 2 semanas, podrías perder prioridad en asignación de OC. Contacta a tu ejecutivo de cuenta para un plan de mejora.',
                'datos' => ['proveedor_id' => $prov3->id, 'score_total' => 40],
                'nivel' => 'warning',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->subDays(1),
            ]);
        }

        if ($prov4) {
            Alerta::create([
                'tipo' => 'sugerencia_ia',
                'modulo' => 'proveedores',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov4->id,
                'titulo' => 'Sugerencia semanal de Salcom IA',
                'contenido' => '¡Excelente rendimiento! Tu score es 81.5%. Mantén este nivel para seguir siendo proveedor preferente de Salcom. Recuerda revisar tus documentos fiscales en la sección Fiscal para mantenerlos al día.',
                'datos' => ['proveedor_id' => $prov4->id, 'tipo_sugerencia' => 'semanal'],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->startOfWeek()->addHours(8),
            ]);

            // Alerta de OC nueva para Diego
            Alerta::create([
                'tipo' => 'oc_nueva',
                'modulo' => 'inventario',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov4->id,
                'titulo' => 'Nueva OC generada: Pigmento base agua',
                'contenido' => 'Se generó una orden de compra por 90 kg de Pigmento base agua. Monto estimado: $10,800.00. Revisa los detalles en Consultar OC.',
                'datos' => ['producto_codigo' => 'SAL-005', 'cantidad' => 90, 'monto_estimado' => 10800],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->subHours(4),
            ]);

            // Alerta de documento por vencer
            Alerta::create([
                'tipo' => 'documento_por_vencer',
                'modulo' => 'fiscal',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov4->id,
                'titulo' => 'Tu Opinión de cumplimiento SAT vence en 5 días',
                'contenido' => 'Recuerda renovar tu documento "Opinión de cumplimiento SAT" antes del ' . now()->addDays(5)->format('d/m/Y') . '. Puedes subirlo desde la sección Fiscal de tu portal.',
                'datos' => ['proveedor_id' => $prov4->id, 'documento_tipo' => 'opinion', 'dias_restantes' => 5],
                'nivel' => 'warning',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->subHours(2),
            ]);
        }

        // --- Alertas para PROV001 (OC nueva) ---
        if ($prov1) {
            Alerta::create([
                'tipo' => 'oc_nueva',
                'modulo' => 'inventario',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov1->id,
                'titulo' => 'Nueva OC generada: Resina epóxica industrial',
                'contenido' => 'Se generó una orden de compra por 90 kg de Resina epóxica industrial. Monto estimado: $7,650.00. Revisa los detalles en Consultar OC.',
                'datos' => ['producto_codigo' => 'SAL-001', 'cantidad' => 90, 'monto_estimado' => 7650],
                'nivel' => 'info',
                'estatus' => 'enviada',
                'canal_enviado' => 'portal',
                'created_at' => now()->subHours(6),
            ]);
        }

        // ══════════════════════════════════════
        // 4. OC AUTO-GENERADAS POR IA
        // ══════════════════════════════════════
        OcBorrador::query()->delete();

        // OC para PROV001 (mejor score)
        if ($prov1) {
            OcBorrador::create([
                'tipo' => 'automatica',
                'proveedor_id' => $prov1->id,
                'productos' => [
                    ['codigo' => 'SAL-001', 'nombre' => 'Resina epóxica industrial', 'cantidad' => 90, 'unidad' => 'kg', 'precio_estimado' => 85],
                    ['codigo' => 'SAL-007', 'nombre' => 'Catalizador rápido', 'cantidad' => 27, 'unidad' => 'kg', 'precio_estimado' => 210],
                ],
                'monto_estimado' => 13320,
                'motivo' => 'Stock bajo mínimo. Resina: 5 kg (mín: 15). Catalizador: 3 kg (mín: 10).',
                'estatus' => 'aprobada',
                'aprobada_por' => null,
                'aprobada_at' => now()->subHours(6),
                'notas' => 'Auto-aprobada por IA',
                'created_at' => now()->subHours(6),
            ]);
        }

        // OC para Diego Cocca
        if ($prov4) {
            OcBorrador::create([
                'tipo' => 'automatica',
                'proveedor_id' => $prov4->id,
                'productos' => [
                    ['codigo' => 'SAL-005', 'nombre' => 'Pigmento base agua', 'cantidad' => 90, 'unidad' => 'kg', 'precio_estimado' => 120],
                ],
                'monto_estimado' => 10800,
                'motivo' => 'Stock agotado. Pigmento base agua: 0 kg.',
                'estatus' => 'aprobada',
                'aprobada_por' => null,
                'aprobada_at' => now()->subHours(4),
                'notas' => 'Auto-aprobada por IA',
                'created_at' => now()->subHours(4),
            ]);

            OcBorrador::create([
                'tipo' => 'automatica',
                'proveedor_id' => $prov4->id,
                'productos' => [
                    ['codigo' => 'SAL-013', 'nombre' => 'Adhesivo estructural', 'cantidad' => 54, 'unidad' => 'kg', 'precio_estimado' => 180],
                ],
                'monto_estimado' => 9720,
                'motivo' => 'Stock agotado. Adhesivo estructural: 0 kg.',
                'estatus' => 'aprobada',
                'aprobada_por' => null,
                'aprobada_at' => now()->subHours(3),
                'notas' => 'Auto-aprobada por IA',
                'created_at' => now()->subHours(3),
            ]);
        }

        // OC para PROV002
        if ($prov2) {
            OcBorrador::create([
                'tipo' => 'automatica',
                'proveedor_id' => $prov2->id,
                'productos' => [
                    ['codigo' => 'SAL-003', 'nombre' => 'Solvente grado técnico', 'cantidad' => 200, 'unidad' => 'lt', 'precio_estimado' => 42.5],
                ],
                'monto_estimado' => 8500,
                'motivo' => 'Reabastecimiento programado. Consumo proyectado alto.',
                'estatus' => 'aprobada',
                'aprobada_por' => null,
                'aprobada_at' => now()->subDays(2),
                'notas' => 'Auto-aprobada por IA',
                'created_at' => now()->subDays(2),
            ]);
        }

        echo "✅ Datos de demo cargados correctamente.\n";
        echo "   - 4 proveedores con scores OTIF variados\n";
        echo "   - Productos con stock bajo (para disparar IA)\n";
        echo "   - Alertas y sugerencias semanales\n";
        echo "   - OC auto-generadas por IA\n";
    }
}
