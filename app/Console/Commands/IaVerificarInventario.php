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
 * Verifica niveles de inventario y genera OC automáticas cuando
 * el stock cae por debajo del mínimo.
 *
 * Fórmula:
 * - Stock Mínimo = Consumo Diario × Días entrega proveedor
 * - Stock Máximo = Consumo Diario × DDI (90 días)
 * - Cantidad a Pedir = Stock Máximo - Existencia - Pendiente de Recibir
 *
 * Se ejecuta cada 4 horas en horario laboral (08:00 - 18:00)
 */
class IaVerificarInventario extends Command
{
    protected $signature = 'ia:verificar-inventario';

    protected $description = 'Verifica niveles de inventario y genera OC cuando stock < mínimo';

    public function handle(): int
    {
        $this->info('📦 Verificando niveles de inventario...');

        $alertEngine = new AlertEngineService;
        $calcService = new InventarioCalculoService;

        $productos = Producto::where('activo', true)->get();
        $alertasGeneradas = 0;
        $ocGeneradas = 0;
        $productosBajos = [];

        foreach ($productos as $producto) {
            // Calcular consumo (por ahora estimado, cuando Alan conecte la API será real)
            $consumoMensual = $this->estimarConsumoMensual($producto);
            $consumoDiario = $calcService->calcularConsumoDiario($consumoMensual);

            if ($consumoDiario <= 0) {
                continue; // Sin consumo, no evaluar
            }

            $diasEntrega = 15; // TODO: obtener de relación producto-proveedor
            $pendienteRecibir = 0; // TODO: obtener de OC pendientes

            $stockMinimo = $calcService->calcularMinimo($consumoDiario, $diasEntrega);
            $stockMaximo = $calcService->calcularMaximo($consumoDiario);
            $cantidadAPedir = $calcService->calcularCantidadAPedir($stockMaximo, $producto->stock, $pendienteRecibir);
            $estado = $calcService->evaluarEstado($producto->stock, $stockMinimo, $stockMaximo);

            // Si está bajo mínimo o agotado → generar alerta + OC
            if ($estado === 'bajo_minimo' || $estado === 'agotado') {
                $productosBajos[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'existencia' => $producto->stock,
                    'minimo' => $stockMinimo,
                    'maximo' => $stockMaximo,
                    'cantidad_a_pedir' => $cantidadAPedir,
                    'estado' => $estado,
                    'unidad' => $producto->unidad_venta,
                ];

                // Verificar que no haya ya una OC pendiente para este producto
                $ocExistente = OcBorrador::where('estatus', 'pendiente')
                    ->whereJsonContains('productos', [['codigo' => $producto->codigo]])
                    ->exists();

                if (! $ocExistente && $cantidadAPedir > 0) {
                    // Buscar mejor proveedor (mayor score)
                    $mejorProveedor = ProveedorUser::where('activo', true)
                        ->where('score_total', '>', 0)
                        ->orderByDesc('score_total')
                        ->first();

                    if ($mejorProveedor) {
                        // Crear OC auto-aprobada por IA (sin intervención del admin)
                        $oc = OcBorrador::create([
                            'tipo' => 'automatica',
                            'proveedor_id' => $mejorProveedor->id,
                            'productos' => [[
                                'codigo' => $producto->codigo,
                                'nombre' => $producto->nombre,
                                'cantidad' => $cantidadAPedir,
                                'unidad' => $producto->unidad_venta,
                                'precio_estimado' => $producto->precio,
                            ]],
                            'monto_estimado' => $cantidadAPedir * $producto->precio,
                            'motivo' => "Stock bajo mínimo. Existencia: {$producto->stock}, Mínimo: {$stockMinimo}",
                            'estatus' => 'aprobada',
                            'aprobada_por' => null,
                            'aprobada_at' => now(),
                            'notas' => 'Auto-aprobada por IA',
                        ]);

                        $ocGeneradas++;

                        // Notificar al proveedor directamente
                        $alertEngine->alertar([
                            'tipo' => 'oc_nueva',
                            'modulo' => 'inventario',
                            'destinatario_tipo' => 'proveedor',
                            'destinatario_id' => $mejorProveedor->id,
                            'titulo' => "Nueva OC generada: {$producto->nombre}",
                            'contenido' => "Se generó una orden de compra por {$cantidadAPedir} {$producto->unidad_venta} de {$producto->nombre}. Monto estimado: $".number_format($oc->monto_estimado, 2).'. Revisa los detalles en Consultar OC.',
                            'datos' => [
                                'oc_id' => $oc->id,
                                'producto_codigo' => $producto->codigo,
                                'cantidad' => $cantidadAPedir,
                                'monto_estimado' => $oc->monto_estimado,
                            ],
                            'nivel' => 'info',
                        ]);
                        $alertasGeneradas++;
                    } else {
                        // Sin proveedor disponible — esto sí requiere atención del admin
                        $alertEngine->alertar([
                            'tipo' => 'stock_bajo_sin_proveedor',
                            'modulo' => 'inventario',
                            'destinatario_tipo' => 'admin',
                            'destinatario_id' => 1,
                            'titulo' => "Stock bajo sin proveedor: {$producto->nombre}",
                            'contenido' => "El producto {$producto->nombre} ({$producto->codigo}) está bajo mínimo pero no hay proveedor activo con score asignado. Requiere asignación manual.",
                            'datos' => [
                                'producto_codigo' => $producto->codigo,
                                'existencia' => $producto->stock,
                                'minimo' => $stockMinimo,
                            ],
                            'nivel' => 'critical',
                        ]);
                        $alertasGeneradas++;
                    }
                }
            }
        }

        $this->info('✅ Verificación completada.');
        $this->info("   Productos evaluados: {$productos->count()}");
        $this->info('   Productos bajo mínimo: '.count($productosBajos));
        $this->info("   OC generadas: {$ocGeneradas}");
        $this->info("   Alertas: {$alertasGeneradas}");

        Log::info("[ia:verificar-inventario] Evaluados: {$productos->count()}, Bajo mínimo: ".count($productosBajos).", OC: {$ocGeneradas}");

        return Command::SUCCESS;
    }

    /**
     * Estimar consumo mensual de un producto.
     * TODO: Reemplazar con datos reales de pedidos cuando Alan conecte la API.
     */
    private function estimarConsumoMensual(Producto $producto): float
    {
        // Estimación basada en stock actual (30% del stock = consumo mensual aprox)
        // En producción: sumar cantidades vendidas últimos 3 meses / 3
        return max(0, round($producto->stock * 0.3));
    }
}
