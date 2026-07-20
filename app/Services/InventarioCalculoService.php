<?php

namespace App\Services;

use App\Models\AlertaConfiguracion;
use App\Models\Pedido;
use App\Models\Producto;

/**
 * Servicio de cálculo de inventario con fórmula de mínimos y máximos.
 *
 * Fórmulas:
 * - Stock Mínimo = Consumo Diario × Días de entrega del proveedor
 * - Stock Máximo = Consumo Diario × DDI (90 días por defecto)
 * - Cantidad a Pedir = Stock Máximo - Existencia - Pendiente de Recibir
 * - Consumo Diario = Consumo Mensual / 30
 * - Días de Inventario = Existencia / Consumo Diario
 */
class InventarioCalculoService
{
    private int $ddi;

    public function __construct()
    {
        $this->ddi = (int) AlertaConfiguracion::get('ddi_dias', 90);
    }

    /**
     * Stock Mínimo = Consumo Diario × Días de entrega del proveedor
     */
    public function calcularMinimo(float $consumoDiario, int $diasEntrega): float
    {
        return round($consumoDiario * $diasEntrega, 2);
    }

    /**
     * Stock Máximo = Consumo Diario × DDI
     */
    public function calcularMaximo(float $consumoDiario): float
    {
        return round($consumoDiario * $this->ddi, 2);
    }

    /**
     * Cantidad a Pedir = Stock Máximo - Existencia - Pendiente de Recibir
     */
    public function calcularCantidadAPedir(float $maximo, float $existencia, float $pendiente): float
    {
        return max(0, round($maximo - $existencia - $pendiente, 2));
    }

    /**
     * Consumo Diario = Consumo Mensual / 30
     */
    public function calcularConsumoDiario(float $consumoMensual): float
    {
        return round($consumoMensual / 30, 2);
    }

    /**
     * Días de Inventario = Existencia / Consumo Diario
     */
    public function calcularDiasInventario(float $existencia, float $consumoDiario): int
    {
        if ($consumoDiario <= 0) {
            return 0;
        }

        return (int) round($existencia / $consumoDiario);
    }

    /**
     * Evaluar estado del producto según su existencia vs mínimo/máximo.
     */
    public function evaluarEstado(float $existencia, float $minimo, float $maximo): string
    {
        if ($existencia <= 0) {
            return 'agotado';
        }
        if ($existencia < $minimo) {
            return 'bajo_minimo';
        }
        if ($existencia > $maximo) {
            return 'sobre_stock';
        }

        return 'ok';
    }

    /**
     * Generar reporte completo de inventario para todos los productos activos.
     */
    public function generarReporteCompleto(): array
    {
        $productos = Producto::where('activo', true)->orderBy('codigo')->get();
        $diasEntrega = (int) AlertaConfiguracion::get('dias_entrega_proveedor', 15);
        $consumoPorCodigo = $this->indexarConsumoMensualPorCodigo();

        $reporte = [];

        foreach ($productos as $producto) {
            $consumoMensual = $consumoPorCodigo[$producto->codigo] ?? 0.0;
            $consumoDiario = $this->calcularConsumoDiario($consumoMensual);
            $pendienteRecibir = 0; // TODO: Obtener de OC pendientes

            $minimo = $this->calcularMinimo($consumoDiario, $diasEntrega);
            $maximo = $this->calcularMaximo($consumoDiario);
            $cantidadAPedir = $this->calcularCantidadAPedir($maximo, $producto->stock, $pendienteRecibir);
            $diasInventario = $this->calcularDiasInventario($producto->stock, $consumoDiario);
            $estado = $this->evaluarEstado($producto->stock, $minimo, $maximo);

            $reporte[] = [
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'existencia' => $producto->stock,
                'consumo_mensual' => $consumoMensual,
                'consumo_diario' => $consumoDiario,
                'dias_entrega' => $diasEntrega,
                'stock_minimo' => $minimo,
                'stock_maximo' => $maximo,
                'dias_inventario' => $diasInventario,
                'pendiente_recibir' => $pendienteRecibir,
                'cantidad_a_pedir' => $cantidadAPedir,
                'estado' => $estado,
                'unidad' => $producto->unidad_venta,
            ];
        }

        return $reporte;
    }

    /**
     * Una pasada sobre pedidos → consumo mensual estimado por código de producto.
     *
     * @return array<string, float>
     */
    private function indexarConsumoMensualPorCodigo(): array
    {
        $pedidos = Pedido::where('created_at', '>=', now()->subMonths(3))
            ->whereNotIn('estatus', ['cancelado'])
            ->get(['productos', 'created_at']);

        $totales = [];
        $meses = [];

        foreach ($pedidos as $pedido) {
            $mes = $pedido->created_at->format('Y-m');
            foreach ($pedido->productos ?? [] as $item) {
                $codigo = (string) ($item['sku'] ?? $item['codigo'] ?? '');
                if ($codigo === '') {
                    continue;
                }
                $totales[$codigo] = ($totales[$codigo] ?? 0) + (float) ($item['cantidad'] ?? 0);
                $meses[$codigo][$mes] = true;
            }
        }

        $resultado = [];
        foreach ($totales as $codigo => $cantidad) {
            $nMeses = count($meses[$codigo] ?? []);
            $resultado[$codigo] = $nMeses > 0 ? round($cantidad / $nMeses, 2) : 0.0;
        }

        return $resultado;
    }

    /**
     * Estimar consumo mensual basado en pedidos de los últimos 3 meses.
     */
    private function estimarConsumoMensual(Producto $producto): float
    {
        return $this->indexarConsumoMensualPorCodigo()[$producto->codigo] ?? 0.0;
    }

    /**
     * Obtener DDI actual.
     */
    public function getDDI(): int
    {
        return $this->ddi;
    }

    /**
     * Calcular OC sugerida con la fórmula de Salcom:
     * OC sugerida = (Consumo promedio 3 meses + Alto mensual del año) / 2
     *
     * Ejemplo: Promedio 3 meses = 100, Alto del año = 150
     * (100 + 150) / 2 = 125 ← cantidad a comprar
     */
    public function calcularOCSugerida(float $consumoPromedio3Meses, float $altoMensualAnual): float
    {
        return round(($consumoPromedio3Meses + $altoMensualAnual) / 2, 2);
    }
}
