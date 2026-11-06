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
    public function mostrarConsultarOC(\App\Services\ProveedorApiService $wieseApi)
    {
        // POR QUÉ cambió: antes esta pantalla mostraba OC "generadas por IA" (tabla local OcBorrador
        // según inventario). Said pidió que el proveedor vea sus ÓRDENES DE COMPRA REALES de Wiese
        // (las mismas que el admin ve en Detalle de adeudos), no sugerencias de IA. Ahora se traen
        // de Wiese por el código del proveedor (CIDDOCUMENTODE=17 = órdenes de compra).
        $proveedor = ProveedorUser::find(session('proveedor_id'));
        $wieseCodigo = trim((string) ($proveedor?->id_proveedor ?? $proveedor?->codigo ?? ''));

        $ordenes = collect();
        $wieseError = null;

        if ($wieseCodigo === '') {
            $wieseError = 'Tu cuenta aún no tiene código del sistema contable. Contacta a Salcom.';
        } else {
            try {
                // Ventana amplia: desde inicio del año anterior hasta hoy (cubre el historial útil).
                $desde = now()->subYear()->startOfYear()->format('Y-m-d');
                $hasta = now()->format('Y-m-d');
                $res = $wieseApi->listarDocumentosOCPorProveedorFechas($wieseCodigo, $desde, $hasta);
                if ($res['success'] ?? false) {
                    $ordenes = collect($res['data']['items'] ?? [])
                        ->map(function ($oc) {
                            // Estatus de la OC: cancelada / pagada / pendiente (misma regla que el admin).
                            if ((int) ($oc['ccancelado'] ?? 0) === 1) {
                                $estatus = 'cancelada';
                            } elseif ((float) ($oc['cpendiente'] ?? 0) > 0) {
                                $estatus = 'pendiente';
                            } else {
                                $estatus = 'pagada';
                            }

                            return [
                                'folio' => (string) ($oc['cfolio'] ?? $oc['folio'] ?? ''),
                                'serie' => (string) ($oc['cseriedocumento'] ?? $oc['serie'] ?? ''),
                                'fecha' => $oc['cfecha'] ?? $oc['fechaFactura'] ?? null,
                                'vence' => $oc['cfechavencimiento'] ?? $oc['fechaVence'] ?? null,
                                'total' => (float) ($oc['ctotal'] ?? $oc['total'] ?? 0),
                                'pendiente' => (float) ($oc['cpendiente'] ?? 0),
                                'estatus' => $estatus,
                            ];
                        })
                        ->sortByDesc(fn ($o) => (string) ($o['fecha'] ?? ''))
                        ->values();
                } else {
                    $wieseError = $res['message'] ?? 'No se pudieron cargar tus órdenes de compra.';
                }
            } catch (\Throwable $e) {
                $wieseError = 'No se pudo conectar con el sistema contable.';
            }
        }

        $stats = [
            'total' => $ordenes->count(),
            'pendientes' => $ordenes->where('estatus', 'pendiente')->count(),
            'pagadas' => $ordenes->where('estatus', 'pagada')->count(),
            'canceladas' => $ordenes->where('estatus', 'cancelada')->count(),
            'monto_total' => $ordenes->sum('total'),
        ];

        return view('proveedores.consultar-oc', compact('ordenes', 'stats', 'wieseError'));
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
