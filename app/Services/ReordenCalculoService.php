<?php

namespace App\Services;

use App\Models\OcBorrador;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoProveedorPrecio;
use App\Models\ProveedorUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReordenCalculoService
{
    /**
     * Calcula el consumo promedio mensual de un producto en los últimos 2 meses.
     *
     * Obtiene pedidos no cancelados de los últimos 2 meses, suma cantidades
     * del producto por mes, y divide entre la cantidad de meses con consumo (1 o 2).
     * Retorna 0 si no hay pedidos para el producto.
     *
     * @param string $codigoProducto Código SKU del producto
     * @return float Consumo promedio mensual
     */
    public function calcularConsumoPromedio(string $codigoProducto): float
    {
        $fechaInicio = Carbon::now()->subMonths(2)->startOfDay();

        $pedidos = Pedido::where('created_at', '>=', $fechaInicio)
            ->whereNotIn('estatus', ['cancelado'])
            ->get(['productos', 'created_at']);

        $consumoPorMes = [];

        foreach ($pedidos as $pedido) {
            $mes = $pedido->created_at->format('Y-m');

            foreach ($pedido->productos ?? [] as $item) {
                $codigo = (string) ($item['sku'] ?? $item['codigo'] ?? '');

                if ($codigo !== $codigoProducto) {
                    continue;
                }

                $cantidad = (float) ($item['cantidad'] ?? 0);

                if (! isset($consumoPorMes[$mes])) {
                    $consumoPorMes[$mes] = 0.0;
                }

                $consumoPorMes[$mes] += $cantidad;
            }
        }

        $mesesConConsumo = count($consumoPorMes);

        if ($mesesConConsumo === 0) {
            return 0.0;
        }

        $totalConsumo = array_sum($consumoPorMes);

        return round($totalConsumo / $mesesConConsumo, 2);
    }

    /**
     * Calcula la cantidad sugerida a pedir aplicando la fórmula de reorden.
     *
     * Fórmula: ceil(ConsumoPromedio + ConsumoPromedio×0.50 + (ConsumoPromedio/30)×LeadTime)
     *
     * @param float $consumoPromedio Consumo promedio mensual
     * @param int $leadTimeDias Lead time en días del proveedor
     * @return int Cantidad sugerida redondeada al entero superior
     */
    public function calcularCantidadSugerida(float $consumoPromedio, int $leadTimeDias): int
    {
        if ($consumoPromedio <= 0) {
            return 0;
        }

        $consumoDiario = $consumoPromedio / 30;
        $cantidad = $consumoPromedio + ($consumoPromedio * 0.50) + ($consumoDiario * $leadTimeDias);

        return (int) ceil($cantidad);
    }

    /**
     * Ajusta una cantidad al múltiplo de MOQ inmediato superior.
     *
     * Si no hay MOQ definido (null) o MOQ <= 0, retorna la cantidad sin cambios.
     * Si la cantidad ya es múltiplo del MOQ, la retorna tal cual.
     *
     * @param int $cantidad Cantidad a ajustar
     * @param int|null $moq Minimum Order Quantity del proveedor
     * @return int Cantidad ajustada al múltiplo de MOQ superior
     */
    public function ajustarAMOQ(int $cantidad, ?int $moq): int
    {
        if ($moq === null || $moq <= 0) {
            return $cantidad;
        }

        if ($cantidad <= 0) {
            return 0;
        }

        return (int) (ceil($cantidad / $moq) * $moq);
    }

    /**
     * Calcula el punto de reorden para un producto.
     *
     * Fórmula: stock_minimo + (consumo_diario × lead_time_dias)
     *
     * @param float $stockMinimo Stock mínimo configurado para el producto
     * @param float $consumoDiario Consumo diario estimado (consumo_promedio / 30)
     * @param int $leadTimeDias Lead time en días del proveedor
     * @return float Punto de reorden calculado
     */
    public function calcularPuntoReorden(float $stockMinimo, float $consumoDiario, int $leadTimeDias): float
    {
        return $stockMinimo + ($consumoDiario * $leadTimeDias);
    }

    /**
     * Evalúa si un producto requiere reorden considerando pendientes de recibir.
     *
     * Un producto requiere reorden si (stock_actual + pendiente_recibir) ≤ punto_reorden.
     *
     * @param Producto $producto Modelo del producto a evaluar
     * @param float $puntoReorden Punto de reorden calculado
     * @param float $pendienteRecibir Cantidad pendiente de recibir (OC aprobadas/en_proceso)
     * @return bool True si el producto requiere reorden
     */
    public function requiereReorden(Producto $producto, float $puntoReorden, float $pendienteRecibir): bool
    {
        $stockActual = (float) ($producto->stock ?? 0);

        return ($stockActual + $pendienteRecibir) <= $puntoReorden;
    }

    /**
     * Obtiene la cantidad pendiente de recibir para un producto.
     *
     * Busca en OC borradores con estatus 'aprobada' o 'en_proceso' y suma
     * las cantidades sugeridas del producto especificado.
     *
     * @param string $codigoProducto Código SKU del producto
     * @return float Cantidad total pendiente de recibir
     */
    public function obtenerPendienteRecibir(string $codigoProducto): float
    {
        $ocPendientes = OcBorrador::whereIn('estatus', ['aprobada', 'en_proceso'])
            ->get(['productos']);

        $totalPendiente = 0.0;

        foreach ($ocPendientes as $oc) {
            foreach ($oc->productos ?? [] as $item) {
                $codigo = (string) ($item['codigo'] ?? '');

                if ($codigo === $codigoProducto) {
                    $totalPendiente += (float) ($item['cantidad_sugerida'] ?? 0);
                }
            }
        }

        return $totalPendiente;
    }

    /**
     * Selecciona el mejor proveedor para un producto (mayor score_total).
     *
     * Busca en producto_proveedor_precios los proveedores vinculados al producto,
     * filtra por proveedores activos, y selecciona el de mayor score_total.
     * Si no encuentra ningún proveedor, registra una alerta de tipo producto_sin_proveedor.
     *
     * @param Producto $producto Modelo del producto
     * @return ProveedorUser|null El proveedor con mayor score o null si no hay ninguno
     */
    public function seleccionarMejorProveedor(Producto $producto): ?ProveedorUser
    {
        $proveedorIds = ProductoProveedorPrecio::where('producto_id', $producto->id)
            ->pluck('proveedor_id')
            ->unique()
            ->toArray();

        if (empty($proveedorIds)) {
            $this->registrarAlertaSinProveedor($producto);

            return null;
        }

        $proveedor = ProveedorUser::whereIn('id', $proveedorIds)
            ->where('activo', true)
            ->orderByDesc('score_total')
            ->first();

        if ($proveedor === null) {
            $this->registrarAlertaSinProveedor($producto);

            return null;
        }

        return $proveedor;
    }

    /**
     * Genera OC borradores agrupados por proveedor.
     *
     * Recibe un array de productos que requieren reorden con su proveedor seleccionado
     * y cantidad sugerida, agrupa por proveedor, busca precios, y crea un OcBorrador
     * por cada proveedor con estatus pendiente y tipo automatica_reorden.
     *
     * @param array $productosReorden Array de items con keys: producto, proveedor, cantidad_sugerida, punto_reorden, urgente
     * @return array Array de modelos OcBorrador creados
     */
    public function generarOCBorradores(array $productosReorden): array
    {
        if (empty($productosReorden)) {
            return [];
        }

        // Agrupar por proveedor_id
        $grupos = [];
        foreach ($productosReorden as $item) {
            $proveedorId = $item['proveedor']->id;
            $grupos[$proveedorId][] = $item;
        }

        $ocCreadas = [];

        foreach ($grupos as $proveedorId => $items) {
            $productosJson = [];
            $montoEstimado = 0.0;

            foreach ($items as $item) {
                /** @var Producto $producto */
                $producto = $item['producto'];
                /** @var ProveedorUser $proveedor */
                $proveedor = $item['proveedor'];
                $cantidadSugerida = (int) $item['cantidad_sugerida'];

                // Obtener precio del proveedor; fallback a producto.precio
                $precioUnitario = $this->obtenerPrecioProveedor($producto, $proveedor->id);

                $subtotal = round($cantidadSugerida * $precioUnitario, 2);
                $montoEstimado += $subtotal;

                $productosJson[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'cantidad_sugerida' => $cantidadSugerida,
                    'unidad' => $producto->unidad_venta,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal,
                    'stock_actual' => (float) ($producto->stock ?? 0),
                    'punto_reorden' => (float) $item['punto_reorden'],
                    'urgente' => (bool) $item['urgente'],
                ];
            }

            $oc = OcBorrador::create([
                'tipo' => 'automatica_reorden',
                'proveedor_id' => $proveedorId,
                'productos' => $productosJson,
                'monto_estimado' => round($montoEstimado, 2),
                'motivo' => 'Generada automáticamente por punto de reorden',
                'estatus' => 'pendiente',
            ]);

            $ocCreadas[] = $oc;
        }

        return $ocCreadas;
    }

    /**
     * Obtiene el precio de un producto para un proveedor específico.
     *
     * Busca en producto_proveedor_precios; si no encuentra, usa producto.precio como fallback.
     *
     * @param Producto $producto Modelo del producto
     * @param int $proveedorId ID del proveedor
     * @return float Precio unitario
     */
    private function obtenerPrecioProveedor(Producto $producto, int $proveedorId): float
    {
        $registro = ProductoProveedorPrecio::where('producto_id', $producto->id)
            ->where('proveedor_id', $proveedorId)
            ->first();

        if ($registro !== null && $registro->precio !== null) {
            return (float) $registro->precio;
        }

        return (float) ($producto->precio ?? 0);
    }

    /**
     * Proceso completo: evalúa todos los productos activos y genera OC borradores.
     *
     * Excluye productos sin stock_minimo configurado (registra advertencia).
     * Excluye productos que ya tienen una OC pendiente/aprobada.
     * Orquesta: consumo → cantidad → reorden → agrupación → OC.
     * Registra log con: productos evaluados, productos reorden, OC generadas, monto total.
     *
     * @return array Resumen con keys: productos_evaluados, productos_reorden, oc_generadas, monto_total
     */
    public function ejecutarProcesoReorden(): array
    {
        // 1. Query all active products
        $productosActivos = Producto::where('activo', true)->get();

        // 2. Separate products with and without stock_minimo
        $productosSinMinimo = $productosActivos->filter(fn ($p) => $p->stock_minimo === null);
        $productosConMinimo = $productosActivos->filter(fn ($p) => $p->stock_minimo !== null);

        // Log warning for products without stock_minimo
        if ($productosSinMinimo->isNotEmpty()) {
            $codigos = $productosSinMinimo->pluck('codigo')->implode(', ');
            Log::warning("[Reorden] Productos sin stock_minimo configurado excluidos del cálculo: {$codigos}", [
                'cantidad' => $productosSinMinimo->count(),
            ]);
        }

        // 3. Get existing OC pendiente/aprobada to check for duplicates (Req 9.2)
        $productosConOcExistente = $this->obtenerProductosConOcExistente();

        $productosEvaluados = $productosConMinimo->count();
        $productosReorden = [];

        foreach ($productosConMinimo as $producto) {
            // 4. Check for duplicates: skip if product already has pending/approved OC
            if (in_array($producto->codigo, $productosConOcExistente, true)) {
                continue;
            }

            // a. Calculate consumo promedio
            $consumoPromedio = $this->calcularConsumoPromedio($producto->codigo);

            // b. Get lead_time_dias (default 15 if null)
            $leadTimeDias = $producto->lead_time_dias ?? 15;

            // c. Calculate cantidad sugerida
            $cantidadSugerida = $this->calcularCantidadSugerida($consumoPromedio, $leadTimeDias);

            // d. Get MOQ and adjust
            $moq = $producto->moqParaProveedor(null);
            // We'll get the MOQ from the best supplier later, but first check reorden
            // For now, use a preliminary check without MOQ adjustment

            // e. Calculate punto de reorden
            $consumoDiario = $consumoPromedio / 30;
            $puntoReorden = $this->calcularPuntoReorden(
                (float) $producto->stock_minimo,
                $consumoDiario,
                $leadTimeDias
            );

            // f. Get pendiente de recibir
            $pendienteRecibir = $this->obtenerPendienteRecibir($producto->codigo);

            // g. Check if requiereReorden
            if (! $this->requiereReorden($producto, $puntoReorden, $pendienteRecibir)) {
                continue;
            }

            // h. Select best supplier
            $proveedor = $this->seleccionarMejorProveedor($producto);

            // i. Skip if no supplier found (alert already registered)
            if ($proveedor === null) {
                continue;
            }

            // Now get MOQ from the selected supplier and adjust cantidad
            $moq = $producto->moqParaProveedor($proveedor->id);
            $cantidadAjustada = $this->ajustarAMOQ($cantidadSugerida, $moq);

            // Determine urgency
            $urgente = ((float) ($producto->stock ?? 0)) == 0;

            $productosReorden[] = [
                'producto' => $producto,
                'proveedor' => $proveedor,
                'cantidad_sugerida' => $cantidadAjustada,
                'punto_reorden' => $puntoReorden,
                'urgente' => $urgente,
            ];
        }

        // 5. Generate OC borradores grouped by supplier
        $ocCreadas = $this->generarOCBorradores($productosReorden);

        // Calculate total amount
        $montoTotal = array_reduce($ocCreadas, function ($carry, $oc) {
            return $carry + (float) $oc->monto_estimado;
        }, 0.0);

        // 6. Log results (Req 10.2)
        Log::info('[Reorden] Proceso de reorden completado', [
            'productos_evaluados' => $productosEvaluados,
            'productos_reorden' => count($productosReorden),
            'oc_generadas' => count($ocCreadas),
            'monto_total' => round($montoTotal, 2),
        ]);

        // 7. Return summary
        return [
            'productos_evaluados' => $productosEvaluados,
            'productos_reorden' => count($productosReorden),
            'oc_generadas' => count($ocCreadas),
            'monto_total' => round($montoTotal, 2),
        ];
    }

    /**
     * Obtiene los códigos de productos que ya tienen una OC borrador pendiente o aprobada.
     *
     * @return array<string> Códigos de productos con OC existente
     */
    private function obtenerProductosConOcExistente(): array
    {
        $ocExistentes = OcBorrador::whereIn('estatus', ['pendiente', 'aprobada'])
            ->get(['productos']);

        $codigosExistentes = [];

        foreach ($ocExistentes as $oc) {
            foreach ($oc->productos ?? [] as $item) {
                $codigo = (string) ($item['codigo'] ?? '');
                if ($codigo !== '') {
                    $codigosExistentes[] = $codigo;
                }
            }
        }

        return array_unique($codigosExistentes);
    }

    /**
     * Registra una alerta de tipo producto_sin_proveedor.
     */
    private function registrarAlertaSinProveedor(Producto $producto): void
    {
        $alertEngine = app(AlertEngineService::class);

        $alertEngine->crearAlerta([
            'tipo' => 'producto_sin_proveedor',
            'modulo' => 'reorden',
            'destinatario_tipo' => 'admin',
            'destinatario_id' => null,
            'titulo' => "Producto sin proveedor: {$producto->codigo} - {$producto->nombre}",
            'contenido' => "El producto {$producto->codigo} ({$producto->nombre}) no tiene un proveedor activo vinculado y fue excluido del proceso de reorden automático.",
            'datos' => [
                'producto_id' => $producto->id,
                'producto_codigo' => $producto->codigo,
            ],
            'nivel' => 'warning',
        ]);
    }
}
