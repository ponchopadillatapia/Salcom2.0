<?php

namespace App\Http\Controllers;

use App\Models\OcBorrador;
use App\Models\Producto;
use App\Models\ProductoProveedorPrecio;
use App\Services\AuditService;
use App\Services\ReordenCalculoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReordenOcController extends Controller
{
    /**
     * Lista OC borradores pendientes de tipo automatica_reorden con resumen.
     * Requerimientos: 7.1, 7.2, 7.4
     */
    public function index(): View
    {
        $ordenes = OcBorrador::where('tipo', 'automatica_reorden')
            ->where('estatus', 'pendiente')
            ->with('proveedor')
            ->orderByDesc('created_at')
            ->get();

        // Calcular resumen
        $totalPendientes = $ordenes->count();
        $montoTotalEstimado = $ordenes->sum('monto_estimado');

        // Contar productos urgentes (stock_actual == 0) across all OCs
        $productosUrgentes = $ordenes->sum(function ($oc) {
            $productos = $oc->productos ?? [];

            return collect($productos)->where('urgente', true)->count();
        });

        return view('admin.reorden-oc.index', [
            'ordenes' => $ordenes,
            'totalPendientes' => $totalPendientes,
            'montoTotalEstimado' => $montoTotalEstimado,
            'productosUrgentes' => $productosUrgentes,
        ]);
    }

    /**
     * Detalle de una OC borrador con lista de productos.
     * Requerimientos: 7.3
     */
    public function show(OcBorrador $oc): View
    {
        $oc->load('proveedor');

        return view('admin.reorden-oc.show', [
            'oc' => $oc,
        ]);
    }

    /**
     * Aprobar una OC borrador.
     * Requerimientos: 8.1, 10.1
     */
    public function aprobar(Request $request, OcBorrador $oc): RedirectResponse
    {
        $estatusAnterior = $oc->estatus;

        $oc->update([
            'estatus' => 'aprobada',
            'aprobada_por' => session('admin_id'),
            'aprobada_at' => now(),
        ]);

        AuditService::registrar(
            'aprobar',
            'reorden-oc',
            "OC borrador #{$oc->id} aprobada",
            ['estatus' => $estatusAnterior],
            ['estatus' => 'aprobada', 'aprobada_por' => session('admin_id'), 'aprobada_at' => $oc->aprobada_at->toISOString()]
        );

        return redirect()->back()->with('mensaje', "OC #{$oc->id} aprobada exitosamente.");
    }

    /**
     * Rechazar una OC borrador.
     * Requerimientos: 8.3, 10.1
     */
    public function rechazar(Request $request, OcBorrador $oc): RedirectResponse
    {
        $request->validate([
            'motivo' => 'required|string|max:1000',
        ]);

        $estatusAnterior = $oc->estatus;

        $oc->update([
            'estatus' => 'rechazada',
            'notas' => $request->input('motivo'),
        ]);

        AuditService::registrar(
            'rechazar',
            'reorden-oc',
            "OC borrador #{$oc->id} rechazada: {$request->input('motivo')}",
            ['estatus' => $estatusAnterior],
            ['estatus' => 'rechazada', 'notas' => $request->input('motivo')]
        );

        return redirect()->back()->with('mensaje', "OC #{$oc->id} rechazada.");
    }

    /**
     * Modificar cantidades de productos en una OC borrador.
     * Requerimientos: 8.2, 10.3
     */
    public function actualizarProductos(Request $request, OcBorrador $oc): RedirectResponse
    {
        $request->validate([
            'cantidades' => 'required|array',
            'cantidades.*' => 'required|numeric|min:1',
        ]);

        $cantidades = $request->input('cantidades');
        $productos = $oc->productos ?? [];
        $historial = $oc->historial_modificaciones ?? [];

        foreach ($productos as $index => &$producto) {
            $codigo = $producto['codigo'];

            if (isset($cantidades[$codigo])) {
                $nuevaCantidad = (int) $cantidades[$codigo];
                $cantidadAnterior = $producto['cantidad_sugerida'];

                if ($nuevaCantidad !== $cantidadAnterior) {
                    $producto['cantidad_sugerida'] = $nuevaCantidad;
                    $producto['subtotal'] = round($nuevaCantidad * $producto['precio_unitario'], 2);

                    $historial[] = [
                        'fecha' => now()->toISOString(),
                        'usuario_id' => session('admin_id'),
                        'usuario_nombre' => session('admin_nombre'),
                        'accion' => 'modificar_cantidad',
                        'producto_codigo' => $codigo,
                        'valor_anterior' => $cantidadAnterior,
                        'valor_nuevo' => $nuevaCantidad,
                        'nota' => null,
                    ];
                }
            }
        }
        unset($producto);

        $montoEstimado = collect($productos)->sum('subtotal');

        $oc->update([
            'productos' => $productos,
            'monto_estimado' => $montoEstimado,
            'historial_modificaciones' => $historial,
        ]);

        return redirect()->back()->with('mensaje', 'Cantidades actualizadas correctamente.');
    }

    /**
     * Eliminar un producto de la OC borrador.
     * Requerimientos: 8.4, 10.3
     */
    public function eliminarProducto(Request $request, OcBorrador $oc): RedirectResponse
    {
        $request->validate([
            'codigo' => 'required|string',
        ]);

        $codigo = $request->input('codigo');
        $productos = $oc->productos ?? [];
        $historial = $oc->historial_modificaciones ?? [];

        // Find and remove the product
        $productoEliminado = null;
        $productosActualizados = [];

        foreach ($productos as $producto) {
            if ($producto['codigo'] === $codigo) {
                $productoEliminado = $producto;
            } else {
                $productosActualizados[] = $producto;
            }
        }

        if (!$productoEliminado) {
            return redirect()->back()->with('error', "Producto con código {$codigo} no encontrado en la OC.");
        }

        // Record in history
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => session('admin_id'),
            'usuario_nombre' => session('admin_nombre'),
            'accion' => 'eliminar_producto',
            'producto_codigo' => $codigo,
            'valor_anterior' => $productoEliminado['cantidad_sugerida'],
            'valor_nuevo' => null,
            'nota' => null,
        ];

        // If no products remain, set status to "rechazada"
        if (empty($productosActualizados)) {
            $oc->update([
                'productos' => $productosActualizados,
                'monto_estimado' => 0,
                'estatus' => 'rechazada',
                'historial_modificaciones' => $historial,
            ]);

            AuditService::registrar(
                'rechazar',
                'reorden-oc',
                "OC borrador #{$oc->id} rechazada automáticamente: sin productos",
                ['estatus' => 'pendiente'],
                ['estatus' => 'rechazada']
            );

            return redirect()->back()->with('mensaje', "Producto eliminado. OC #{$oc->id} rechazada por quedar vacía.");
        }

        $montoEstimado = collect($productosActualizados)->sum('subtotal');

        $oc->update([
            'productos' => $productosActualizados,
            'monto_estimado' => $montoEstimado,
            'historial_modificaciones' => $historial,
        ]);

        AuditService::registrar(
            'editar',
            'reorden-oc',
            "Producto {$codigo} eliminado de OC borrador #{$oc->id}",
            ['producto' => $productoEliminado],
            ['productos_restantes' => count($productosActualizados)]
        );

        return redirect()->back()->with('mensaje', "Producto {$codigo} eliminado de la OC.");
    }

    /**
     * Agregar un producto a la OC borrador.
     * Requerimientos: 8.5, 10.3
     */
    public function agregarProducto(Request $request, OcBorrador $oc): RedirectResponse
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|numeric|min:1',
        ]);

        $productoId = $request->input('producto_id');
        $cantidad = (int) $request->input('cantidad');

        $producto = Producto::findOrFail($productoId);

        // Validate the product belongs to the same supplier
        $precioProveedor = ProductoProveedorPrecio::where('producto_id', $productoId)
            ->where('proveedor_id', $oc->proveedor_id)
            ->first();

        if (!$precioProveedor) {
            return redirect()->back()->with('error', 'El producto no pertenece al proveedor de esta OC.');
        }

        // Get price: from ProductoProveedorPrecio or fallback to producto.precio
        $precioUnitario = $precioProveedor->precio ? (float) $precioProveedor->precio : (float) $producto->precio;
        $subtotal = round($cantidad * $precioUnitario, 2);

        $productos = $oc->productos ?? [];
        $historial = $oc->historial_modificaciones ?? [];

        // Add new product entry
        $productos[] = [
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'cantidad_sugerida' => $cantidad,
            'unidad' => $producto->unidad_venta,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'stock_actual' => (float) $producto->stock,
            'punto_reorden' => null,
            'urgente' => ((float) $producto->stock) == 0,
        ];

        $montoEstimado = collect($productos)->sum('subtotal');

        // Record in history
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => session('admin_id'),
            'usuario_nombre' => session('admin_nombre'),
            'accion' => 'agregar_producto',
            'producto_codigo' => $producto->codigo,
            'valor_anterior' => null,
            'valor_nuevo' => $cantidad,
            'nota' => null,
        ];

        $oc->update([
            'productos' => $productos,
            'monto_estimado' => $montoEstimado,
            'historial_modificaciones' => $historial,
        ]);

        return redirect()->back()->with('mensaje', "Producto {$producto->codigo} agregado a la OC.");
    }

    /**
     * Ejecutar el proceso de reorden manualmente desde el dashboard.
     * Requerimientos: 9.1
     */
    public function ejecutarReorden(): RedirectResponse
    {
        $servicio = app(ReordenCalculoService::class);

        $resultado = $servicio->ejecutarProcesoReorden();

        $mensaje = sprintf(
            'Proceso de reorden ejecutado: %d productos evaluados, %d requieren reorden, %d OC generadas (monto total: $%s).',
            $resultado['productos_evaluados'],
            $resultado['productos_reorden'],
            $resultado['oc_generadas'],
            number_format($resultado['monto_total'], 2)
        );

        return redirect()->back()->with('mensaje', $mensaje);
    }

    /**
     * Importar stock mínimos desde archivo Excel o CSV.
     * Formato esperado: Columna A = código de producto, Columna B = stock_minimo.
     * Requerimientos: 3.2, 3.3
     */
    public function importarStockMinimos(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $archivo = $request->file('archivo');
        $spreadsheet = IOFactory::load($archivo->getPathname());
        $hoja = $spreadsheet->getActiveSheet();

        $exitosos = 0;
        $fallidos = 0;
        $errores = [];

        foreach ($hoja->getRowIterator() as $fila) {
            $celdas = [];
            $cellIterator = $fila->getCellIterator('A', 'B');
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $celda) {
                $celdas[] = $celda->getValue();
            }

            $codigo = trim((string) ($celdas[0] ?? ''));
            $stockMinimo = $celdas[1] ?? null;

            // Skip empty rows or header rows
            if ($codigo === '' || $stockMinimo === null || $stockMinimo === '') {
                continue;
            }

            // Validate that stock_minimo is a positive number > 0
            if (!is_numeric($stockMinimo) || (float) $stockMinimo <= 0) {
                $fallidos++;
                $errores[] = "Fila {$fila->getRowIndex()}: valor inválido para '{$codigo}' (debe ser > 0).";
                continue;
            }

            // Find the product by code
            $producto = Producto::where('codigo', $codigo)->first();

            if (!$producto) {
                $fallidos++;
                $errores[] = "Fila {$fila->getRowIndex()}: producto '{$codigo}' no encontrado.";
                continue;
            }

            // Update stock_minimo
            $producto->stock_minimo = (float) $stockMinimo;
            $producto->save();
            $exitosos++;
        }

        $mensaje = sprintf(
            'Importación completada: %d productos actualizados, %d con errores.',
            $exitosos,
            $fallidos
        );

        if (!empty($errores)) {
            $mensaje .= ' Errores: ' . implode(' ', array_slice($errores, 0, 5));
            if (count($errores) > 5) {
                $mensaje .= sprintf(' ... y %d más.', count($errores) - 5);
            }
        }

        return redirect()->back()->with('mensaje', $mensaje);
    }
}
