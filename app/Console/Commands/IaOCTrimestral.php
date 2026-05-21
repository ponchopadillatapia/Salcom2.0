<?php

namespace App\Console\Commands;

use App\Models\OcBorrador;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use App\Services\InventarioCalculoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Genera OC masiva trimestral basada en la fórmula de mínimos y máximos.
 * Agrupa productos por proveedor y genera una OC consolidada por cada uno.
 *
 * Se ejecuta cada 90 días (trimestralmente)
 */
class IaOCTrimestral extends Command
{
    protected $signature = 'ia:oc-trimestral';

    protected $description = 'Genera OC trimestral masiva basada en fórmula de mínimos y máximos';

    public function handle(): int
    {
        $this->info('📋 Generando OC trimestral...');

        $alertEngine = new AlertEngineService;
        $calcService = new InventarioCalculoService;

        $productos = Producto::where('activo', true)->get();
        $productosAPedir = [];

        // Calcular cantidad a pedir para cada producto
        foreach ($productos as $producto) {
            $consumoMensual = max(0, round($producto->stock * 0.3)); // TODO: datos reales
            $consumoDiario = $calcService->calcularConsumoDiario($consumoMensual);

            if ($consumoDiario <= 0) {
                continue;
            }

            $diasEntrega = 15; // TODO: obtener de relación producto-proveedor
            $pendienteRecibir = 0; // TODO: obtener de OC pendientes

            $stockMaximo = $calcService->calcularMaximo($consumoDiario);
            $cantidadAPedir = $calcService->calcularCantidadAPedir($stockMaximo, $producto->stock, $pendienteRecibir);

            if ($cantidadAPedir > 0) {
                $productosAPedir[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidadAPedir,
                    'unidad' => $producto->unidad_venta,
                    'precio_estimado' => $producto->precio,
                    'monto' => $cantidadAPedir * $producto->precio,
                ];
            }
        }

        if (empty($productosAPedir)) {
            $this->info('✅ No hay productos que requieran reorden trimestral.');

            return Command::SUCCESS;
        }

        // Agrupar por proveedor (por ahora asignar al mejor proveedor disponible)
        // TODO: Cuando tengamos relación producto-proveedor, agrupar correctamente
        $proveedores = ProveedorUser::where('activo', true)
            ->where('score_total', '>', 0)
            ->orderByDesc('score_total')
            ->get();

        if ($proveedores->isEmpty()) {
            $alertEngine->alertar([
                'tipo' => 'oc_trimestral_sin_proveedor',
                'modulo' => 'inventario',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 1,
                'titulo' => '⚠️ OC Trimestral: No hay proveedores disponibles',
                'contenido' => 'Se detectaron '.count($productosAPedir).' productos que necesitan reorden pero no hay proveedores activos con score.',
                'nivel' => 'critical',
            ]);

            return Command::SUCCESS;
        }

        // Distribuir productos entre proveedores (round-robin simplificado)
        $ocsPorProveedor = [];
        $provIndex = 0;
        $totalProveedores = $proveedores->count();

        foreach ($productosAPedir as $prod) {
            $provId = $proveedores[$provIndex % $totalProveedores]->id;
            $ocsPorProveedor[$provId][] = $prod;
            $provIndex++;
        }

        // Crear OC borrador por cada proveedor
        $ocGeneradas = 0;
        foreach ($ocsPorProveedor as $proveedorId => $productos) {
            $montoTotal = collect($productos)->sum('monto');
            $proveedor = $proveedores->firstWhere('id', $proveedorId);

            OcBorrador::create([
                'tipo' => 'trimestral',
                'proveedor_id' => $proveedorId,
                'productos' => $productos,
                'monto_estimado' => $montoTotal,
                'motivo' => 'OC trimestral automática - Reorden basado en fórmula DDI '.$calcService->getDDI().' días',
                'estatus' => 'pendiente',
            ]);
            $ocGeneradas++;
        }

        // Alertar al admin con resumen
        $montoTotalGeneral = collect($productosAPedir)->sum('monto');
        $alertEngine->alertar([
            'tipo' => 'oc_trimestral_generada',
            'modulo' => 'inventario',
            'destinatario_tipo' => 'admin',
            'destinatario_id' => 1,
            'titulo' => "📋 OC Trimestral generada: {$ocGeneradas} órdenes",
            'contenido' => "Se generaron {$ocGeneradas} borradores de OC trimestral para ".count($productosAPedir).' productos. Monto total estimado: $'.number_format($montoTotalGeneral, 2).'. Requieren tu aprobación.',
            'datos' => [
                'total_oc' => $ocGeneradas,
                'total_productos' => count($productosAPedir),
                'monto_total' => $montoTotalGeneral,
                'ddi_dias' => $calcService->getDDI(),
            ],
            'nivel' => 'info',
        ]);

        $this->info('✅ OC Trimestral completada.');
        $this->info('   Productos a reordenar: '.count($productosAPedir));
        $this->info("   OC generadas: {$ocGeneradas}");
        $this->info('   Monto total estimado: $'.number_format($montoTotalGeneral, 2));

        Log::info('[ia:oc-trimestral] Productos: '.count($productosAPedir).", OC: {$ocGeneradas}, Monto: $".number_format($montoTotalGeneral, 2));

        return Command::SUCCESS;
    }
}
