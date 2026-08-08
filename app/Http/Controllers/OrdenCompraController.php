<?php

namespace App\Http\Controllers;

use App\Models\OcBorrador;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use App\Services\InventarioCalculoService;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    public function mostrarConsultarOC()
    {
        $provId = session('proveedor_id');

        // orderBy id evita filesort del JSON `productos` (Out of sort memory en prod).
        $ordenes = OcBorrador::where('proveedor_id', $provId)
            ->orderByDesc('id')
            ->get();

        $stats = [
            'abiertas' => $ordenes->where('estatus', 'aprobada')->count(),
            'completadas' => $ordenes->where('estatus', 'completada')->count(),
            'en_proceso' => $ordenes->where('estatus', 'en_proceso')->count(),
            'monto_total' => $ordenes->sum('monto_estimado'),
        ];

        return view('proveedores.consultar-oc', compact('ordenes', 'stats'));
    }

    /**
     * Generar OC automática para el proveedor logueado.
     * Revisa productos con stock bajo y crea la OC.
     */
    public function generarOC(Request $request)
    {
        $provId = session('proveedor_id');
        $proveedor = ProveedorUser::findOrFail($provId);

        $calcService = new InventarioCalculoService;
        $alertEngine = new AlertEngineService;

        $productos = Producto::where('activo', true)->get();
        $productosOC = [];
        $montoTotal = 0;

        foreach ($productos as $producto) {
            // Si stock es 0 o muy bajo, usar un consumo mínimo estimado basado en precio
            // En producción esto vendrá de datos reales de pedidos
            $consumoMensual = $producto->stock > 0
                ? max(1, round($producto->stock * 0.3))
                : 10; // Estimado mínimo para productos agotados

            $consumoDiario = $calcService->calcularConsumoDiario($consumoMensual);

            if ($consumoDiario <= 0) {
                $consumoDiario = 1; // Mínimo 1 unidad diaria
            }

            $diasEntrega = 15;
            $stockMinimo = $calcService->calcularMinimo($consumoDiario, $diasEntrega);
            $stockMaximo = $calcService->calcularMaximo($consumoDiario);
            $cantidadAPedir = $calcService->calcularCantidadAPedir($stockMaximo, $producto->stock, 0);
            $estado = $calcService->evaluarEstado($producto->stock, $stockMinimo, $stockMaximo);

            // Si stock es 0, forzar estado agotado
            if ($producto->stock <= 0) {
                $estado = 'agotado';
                $cantidadAPedir = max($cantidadAPedir, $stockMaximo);
            }

            if (($estado === 'bajo_minimo' || $estado === 'agotado') && $cantidadAPedir > 0) {
                $productosOC[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'cantidad' => $cantidadAPedir,
                    'unidad' => $producto->unidad_venta,
                    'precio_estimado' => $producto->precio,
                ];
                $montoTotal += $cantidadAPedir * $producto->precio;
            }
        }

        if (empty($productosOC)) {
            return back()->with('mensaje', 'No hay productos que requieran reabastecimiento en este momento.');
        }

        $oc = OcBorrador::create([
            'tipo' => 'manual_proveedor',
            'proveedor_id' => $provId,
            'productos' => $productosOC,
            'monto_estimado' => $montoTotal,
            'motivo' => 'OC generada manualmente por proveedor desde portal',
            'estatus' => 'aprobada',
            'aprobada_por' => null,
            'aprobada_at' => now(),
            'notas' => 'Auto-aprobada por IA',
        ]);

        $alertEngine->alertar([
            'tipo' => 'oc_nueva',
            'modulo' => 'inventario',
            'destinatario_tipo' => 'proveedor',
            'destinatario_id' => $provId,
            'titulo' => "OC #{$oc->id} generada",
            'contenido' => 'Se generó una OC por '.count($productosOC).' productos. Monto: $'.number_format($montoTotal, 2),
            'datos' => ['oc_id' => $oc->id, 'monto' => $montoTotal],
            'nivel' => 'info',
        ]);

        return back()->with('mensaje', "OC #{$oc->id} generada exitosamente. Monto: $".number_format($montoTotal, 2));
    }
}
