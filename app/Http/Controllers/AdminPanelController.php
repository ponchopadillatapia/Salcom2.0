<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    // ── Dashboard general ──

    public function dashboard()
    {
        // Pedidos por mes (últimos 6 meses)
        $pedidosPorMes = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $pedidosPorMes->push([
                'mes' => $fecha->translatedFormat('M Y'),
                'total' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->count(),
                'monto' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->sum('total'),
            ]);
        }

        $facturasPorEstatus = Factura::selectRaw('estatus, count(*) as total, sum(total) as monto')
            ->groupBy('estatus')->get()->keyBy('estatus');

        $data = [
            'totalProveedores'   => ProveedorUser::count(),
            'proveedoresActivos' => ProveedorUser::where('activo', true)->count(),
            'scorePromedio'      => round((float) ProveedorUser::avg('score_total'), 1),
            'totalPedidos'       => Pedido::count(),
            'pedidosPendientes'  => Pedido::whereIn('estatus', ['validacion', 'procesando'])->count(),
            'pedidosEntregados'  => Pedido::where('estatus', 'entregado')->count(),
            'montoPedidos'       => Pedido::sum('total'),
            'totalProductos'     => Producto::count(),
            'sinStock'           => Producto::where('stock', '<=', 0)->count(),
            'facturasPendientes' => Factura::where('estatus', 'pendiente')->count(),
            'montoFacturas'      => Factura::where('estatus', 'pendiente')->sum('total'),
            'docsPendientes'     => DocumentoProveedor::where('estatus', 'pendiente')->count(),
            'ultimosPedidos'     => Pedido::orderBy('created_at', 'desc')->limit(5)->get(),
            'topProveedores'     => ProveedorUser::where('score_total', '>', 0)->orderBy('score_total', 'desc')->limit(5)->get(),
            'pedidosPorMes'      => $pedidosPorMes,
            'facturasPorEstatus' => $facturasPorEstatus,
        ];

        return view('admin.dashboard', $data);
    }

    // ── Lista de Clientes ──

    public function clientes(Request $request)
    {
        $query = ClienteUser::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%");
            });
        }

        $clientes = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.clientes', compact('clientes', 'busqueda'));
    }

    public function toggleCliente(ClienteUser $cliente)
    {
        $cliente->update(['activo' => ! $cliente->activo]);
        $estado = $cliente->activo ? 'activado' : 'desactivado';

        return back()->with('mensaje', "Cliente {$cliente->nombre} {$estado} correctamente.");
    }

    public function eliminarCliente(ClienteUser $cliente)
    {
        $nombre = $cliente->nombre;
        $cliente->delete();

        return back()->with('mensaje', "Cliente \"{$nombre}\" eliminado correctamente.");
    }

    public function eliminarProveedor(ProveedorUser $proveedor)
    {
        $nombre = $proveedor->nombre ?? $proveedor->usuario;
        $proveedor->delete();

        return back()->with('mensaje', "Proveedor \"{$nombre}\" eliminado correctamente.");
    }

    // ── Encuestas ──

    public function encuestas()
    {
        $encuestas = Encuesta::orderBy('created_at', 'desc')->paginate(20);

        $promedioGeneral = Encuesta::avg('calificacion');
        $promedioEntrega = Encuesta::avg('tiempo_entrega');
        $promedioCalidad = Encuesta::avg('calidad_producto');
        $totalEncuestas = Encuesta::count();

        return view('admin.encuestas', compact(
            'encuestas', 'promedioGeneral', 'promedioEntrega',
            'promedioCalidad', 'totalEncuestas'
        ));
    }

    // ── Pedidos ──

    public function pedidos(Request $request)
    {
        $query = Pedido::query();

        if ($estatus = $request->input('estatus')) {
            $query->where('estatus', $estatus);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $estatusDisponibles = Pedido::select('estatus')->distinct()->pluck('estatus');

        return view('admin.pedidos', compact('pedidos', 'estatus', 'estatusDisponibles'));
    }

    // ── Proveedores con Score ──

    public function proveedores(Request $request)
    {
        $query = ProveedorUser::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_compras', 'like', "%{$busqueda}%");
            });
        }

        $proveedores = $query->orderBy('score_total', 'desc')->paginate(20)->withQueryString();

        // Datos adicionales para las secciones
        $ordenes = Factura::whereNotNull('codigo_proveedor')->orderBy('created_at', 'desc')->limit(20)->get();
        $productos = Producto::where('activo', true)->orderBy('codigo')->limit(10)->get();
        $facturasPendientes = Factura::where('estatus', 'pendiente')->whereNotNull('codigo_proveedor')->orderBy('fecha_vencimiento')->get();

        return view('admin.proveedores', compact('proveedores', 'busqueda', 'ordenes', 'productos', 'facturasPendientes'));
    }

    // ── Productos ──

    public function productos(Request $request)
    {
        $query = Producto::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('codigo', 'like', "%{$busqueda}%");
            });
        }

        if ($request->input('sin_stock')) {
            $query->where('stock', '<=', 0);
        }

        $productos = $query->orderBy('codigo')->paginate(20)->withQueryString();
        $busqueda = $request->input('busqueda');
        $sinStock = $request->input('sin_stock');

        return view('admin.productos', compact('productos', 'busqueda', 'sinStock'));
    }

    // ── Facturas ──

    public function facturas(Request $request)
    {
        $estatus = $request->input('estatus');
        $vencidas = $request->input('vencidas');

        $query = Factura::whereNotNull('codigo_proveedor');
        if ($estatus) {
            $query->where('estatus', $estatus);
        }
        if ($vencidas) {
            $query->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        }
        $facturas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.facturas', compact('facturas', 'estatus', 'vencidas'));
    }

    // ── Documentos de proveedores ──

    public function documentos(Request $request)
    {
        $query = DocumentoProveedor::with('proveedor');

        if ($estatus = $request->input('estatus')) {
            $query->where('estatus', $estatus);
        }

        $documentos = $query->orderByRaw("FIELD(estatus, 'pendiente', 'rechazado', 'aprobado')")->paginate(20)->withQueryString();
        $estatus = $request->input('estatus');

        return view('admin.documentos', compact('documentos', 'estatus'));
    }

    // ── Negocio ──

    public function negocio()
    {
        $data = [
            'ventasTotales'     => Pedido::whereNotIn('estatus', ['cancelado'])->sum('total'),
            'pedidosEntregados' => Pedido::where('estatus', 'entregado')->count(),
            'totalPedidos'      => Pedido::count(),
            'totalEncuestas'    => Encuesta::count(),
            'calificacionProm'  => round((float) Encuesta::avg('calificacion'), 1),
            'facturasPagadas'   => Factura::where('estatus', 'pagada')->sum('total'),
            'facturasPendientes'=> Factura::where('estatus', 'pendiente')->sum('total'),
            'pedidosPorMes'     => $this->pedidosPorMes(),
            'encuestas'         => Encuesta::orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.negocio', $data);
    }

    // ── OTIF ──

    public function otif()
    {
        // OTIF basado en facturas de proveedores
        $facturasProveedor = Factura::whereNotNull('codigo_proveedor')->get();
        $total      = $facturasProveedor->count();
        $pagadas    = $facturasProveedor->where('estatus', 'pagada')->count();
        $pendientes = $facturasProveedor->where('estatus', 'pendiente')->count();
        $vencidas   = $facturasProveedor->where('estatus', 'pendiente')
            ->filter(fn($f) => $f->fecha_vencimiento && $f->fecha_vencimiento->isPast())->count();
        $aTiempo    = $pagadas; // pagadas = entregadas a tiempo
        $canceladas = $facturasProveedor->where('estatus', 'cancelada')->count();

        $otPercent = $total > 0 ? round(($aTiempo / $total) * 100, 1) : 0;
        $ifPercent = $total > 0 ? round((($total - $canceladas) / $total) * 100, 1) : 0;
        $porcentaje = $total > 0 ? round(($aTiempo / $total) * 100) : 0;

        // Detalle por proveedor
        $proveedores = ProveedorUser::where('activo', true)->orderBy('score_total', 'desc')->get();
        $detalleProveedores = [];
        foreach ($proveedores as $prov) {
            $factProv = Factura::where('codigo_proveedor', $prov->codigo_compras)->get();
            $totalProv = $factProv->count();
            if ($totalProv === 0) continue;

            $pagadasProv = $factProv->where('estatus', 'pagada')->count();
            $otProv = round(($pagadasProv / $totalProv) * 100, 1);

            $detalleProveedores[] = [
                'nombre'  => $prov->nombre ?? $prov->usuario,
                'codigo'  => $prov->codigo_compras,
                'total'   => $totalProv,
                'pagadas' => $pagadasProv,
                'ot'      => $otProv,
                'score'   => $prov->score_total,
            ];
        }

        return view('admin.otif', compact('total', 'pagadas', 'pendientes', 'vencidas', 'canceladas', 'otPercent', 'ifPercent', 'porcentaje', 'detalleProveedores'));
    }

    // ── Inventario ──

    public function inventario()
    {
        $productos   = Producto::where('activo', true)->orderBy('stock', 'asc')->get();
        $totalStock  = Producto::where('activo', true)->sum('stock');
        $sinStock    = Producto::where('activo', true)->where('stock', '<=', 0)->count();
        $stockBajo   = Producto::where('activo', true)->where('stock', '>', 0)->where('stock', '<', 50)->count();
        $stockOk     = Producto::where('activo', true)->where('stock', '>=', 50)->count();

        return view('admin.inventario', compact('productos', 'totalStock', 'sinStock', 'stockBajo', 'stockOk'));
    }

    // ── Helper: pedidos por mes ──

    private function pedidosPorMes()
    {
        $mesesNombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $data = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $data->push([
                'mes'   => $mesesNombres[(int)$fecha->format('n')] . ' ' . $fecha->format('Y'),
                'total' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->count(),
                'monto' => Factura::whereNotNull('codigo_proveedor')->whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->sum('total'),
            ]);
        }
        return $data;
    }

    // ── Reporte de Proveedores (comparativo anual) ──

    public function reporteProveedores()
    {
        $anioActual = (int) date('Y');
        $anioAnterior = $anioActual - 1;

        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $reporte = [];
        $totales = ['compras_anterior' => 0, 'compras_actual' => 0, 'facturas_anterior' => 0, 'facturas_actual' => 0];

        foreach ($proveedores as $prov) {
            $facturasAnterior = Factura::where('codigo_proveedor', $prov->codigo_compras)
                ->whereYear('created_at', $anioAnterior)->get();
            $facturasActual = Factura::where('codigo_proveedor', $prov->codigo_compras)
                ->whereYear('created_at', $anioActual)->get();

            $montoAnterior = $facturasAnterior->sum('total');
            $montoActual = $facturasActual->sum('total');
            $cantAnterior = $facturasAnterior->count();
            $cantActual = $facturasActual->count();

            $variacionMonto = $montoAnterior > 0 ? round((($montoActual - $montoAnterior) / $montoAnterior) * 100, 1) : ($montoActual > 0 ? 100 : 0);
            $variacionCant = $cantAnterior > 0 ? round((($cantActual - $cantAnterior) / $cantAnterior) * 100, 1) : ($cantActual > 0 ? 100 : 0);

            $reporte[] = [
                'codigo'           => $prov->codigo_compras,
                'nombre'           => $prov->nombre ?? $prov->usuario,
                'compras_anterior' => $montoAnterior,
                'compras_actual'   => $montoActual,
                'facturas_anterior'=> $cantAnterior,
                'facturas_actual'  => $cantActual,
                'variacion_monto'  => $variacionMonto,
                'variacion_cant'   => $variacionCant,
                'score'            => $prov->score_total,
            ];

            $totales['compras_anterior'] += $montoAnterior;
            $totales['compras_actual'] += $montoActual;
            $totales['facturas_anterior'] += $cantAnterior;
            $totales['facturas_actual'] += $cantActual;
        }

        $totales['variacion_monto'] = $totales['compras_anterior'] > 0
            ? round((($totales['compras_actual'] - $totales['compras_anterior']) / $totales['compras_anterior']) * 100, 1)
            : 0;

        return view('admin.reporte-proveedores', compact('reporte', 'totales', 'anioActual', 'anioAnterior'));
    }

    public function reporteProveedoresExcel()
    {
        $anioActual = (int) date('Y');
        $anioAnterior = $anioActual - 1;

        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $filename = 'Reporte_Proveedores_' . $anioActual . '.csv';

        // Generar CSV en memoria
        $output = chr(0xEF) . chr(0xBB) . chr(0xBF); // BOM UTF-8

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['REPORTE DE COMPRAS POR PROVEEDOR - COMPARATIVO ANUAL'];
        $lines[] = ['Generado: ' . now()->format('d/m/Y H:i')];
        $lines[] = [];
        $lines[] = ['CODIGO', 'PROVEEDOR', 'SCORE', "FACTURAS {$anioAnterior}", "FACTURAS {$anioActual}", 'VAR FACTURAS %', "COMPRAS {$anioAnterior}", "COMPRAS {$anioActual}", 'VAR COMPRAS %'];

        $totalAnterior = 0;
        $totalActual = 0;

        foreach ($proveedores as $prov) {
            $facturasAnt = Factura::where('codigo_proveedor', $prov->codigo_compras)->whereYear('created_at', $anioAnterior)->get();
            $facturasAct = Factura::where('codigo_proveedor', $prov->codigo_compras)->whereYear('created_at', $anioActual)->get();

            $montoAnt = $facturasAnt->sum('total');
            $montoAct = $facturasAct->sum('total');
            $cantAnt = $facturasAnt->count();
            $cantAct = $facturasAct->count();

            $varMonto = $montoAnt > 0 ? round((($montoAct - $montoAnt) / $montoAnt) * 100, 1) : 0;
            $varCant = $cantAnt > 0 ? round((($cantAct - $cantAnt) / $cantAnt) * 100, 1) : 0;

            $lines[] = [
                $prov->codigo_compras,
                $prov->nombre ?? $prov->usuario,
                $prov->score_total . '%',
                $cantAnt,
                $cantAct,
                $varCant . '%',
                number_format($montoAnt, 2),
                number_format($montoAct, 2),
                $varMonto . '%',
            ];

            $totalAnterior += $montoAnt;
            $totalActual += $montoAct;
        }

        $lines[] = [];
        $varTotal = $totalAnterior > 0 ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1) : 0;
        $lines[] = ['', 'GRAN TOTAL', '', '', '', '', number_format($totalAnterior, 2), number_format($totalActual, 2), $varTotal . '%'];

        // Convertir a CSV string
        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $output .= stream_get_contents($handle);
        fclose($handle);

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── Reporte Corte (Enero a mes actual, desglose mensual) ──

    public function reporteCorte()
    {
        $anio = (int) date('Y');
        $mesActual = (int) date('n');
        $meses = [];
        for ($m = 1; $m <= $mesActual; $m++) {
            $meses[$m] = $this->mesNombre($m);
        }

        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $reporte = [];
        $totalesMes = array_fill(1, $mesActual, 0);
        $granTotal = 0;

        foreach ($proveedores as $prov) {
            $fila = [
                'codigo' => $prov->codigo_compras,
                'nombre' => $prov->nombre ?? $prov->usuario,
                'meses'  => [],
                'total'  => 0,
            ];

            for ($m = 1; $m <= $mesActual; $m++) {
                $monto = Factura::where('codigo_proveedor', $prov->codigo_compras)
                    ->whereYear('created_at', $anio)
                    ->whereMonth('created_at', $m)
                    ->sum('total');
                $fila['meses'][$m] = $monto;
                $fila['total'] += $monto;
                $totalesMes[$m] += $monto;
            }

            $granTotal += $fila['total'];
            $reporte[] = $fila;
        }

        return view('admin.reporte-corte', compact('reporte', 'meses', 'totalesMes', 'granTotal', 'anio', 'mesActual'));
    }

    public function reporteCorteExcel()
    {
        $anio = (int) date('Y');
        $mesActual = (int) date('n');
        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $filename = "Corte_Proveedores_Ene-{$this->mesNombre($mesActual)}_{$anio}.csv";

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ["CORTE DE COMPRAS POR PROVEEDOR - ENERO A " . strtoupper($this->mesNombre($mesActual)) . " {$anio}"];
        $lines[] = ['Generado: ' . now()->format('d/m/Y H:i')];
        $lines[] = [];

        // Header
        $header = ['CODIGO', 'PROVEEDOR'];
        for ($m = 1; $m <= $mesActual; $m++) {
            $header[] = strtoupper(substr($this->mesNombre($m), 0, 3));
        }
        $header[] = 'TOTAL ACUMULADO';
        $lines[] = $header;

        $totalesMes = array_fill(1, $mesActual, 0);
        $granTotal = 0;

        foreach ($proveedores as $prov) {
            $row = [$prov->codigo_compras, $prov->nombre ?? $prov->usuario];
            $totalProv = 0;

            for ($m = 1; $m <= $mesActual; $m++) {
                $monto = Factura::where('codigo_proveedor', $prov->codigo_compras)
                    ->whereYear('created_at', $anio)
                    ->whereMonth('created_at', $m)
                    ->sum('total');
                $row[] = number_format($monto, 2);
                $totalProv += $monto;
                $totalesMes[$m] += $monto;
            }

            $row[] = number_format($totalProv, 2);
            $granTotal += $totalProv;
            $lines[] = $row;
        }

        // Totales
        $lines[] = [];
        $totalRow = ['', 'TOTAL POR MES'];
        for ($m = 1; $m <= $mesActual; $m++) {
            $totalRow[] = number_format($totalesMes[$m], 2);
        }
        $totalRow[] = number_format($granTotal, 2);
        $lines[] = $totalRow;

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $output = stream_get_contents($handle);
        fclose($handle);

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function mesNombre(int $mes): string
    {
        return ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][$mes] ?? '';
    }

    // ── Fiscal — Estado de documentos por proveedor ──

    public function fiscal()
    {
        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $documentosPorProveedor = [];
        $tiposRequeridos = ['cif', 'opinion', 'acta', 'rep_legal', 'contribuyente', 'caratula_banco'];

        foreach ($proveedores as $prov) {
            $docs = DocumentoProveedor::where('proveedor_id', $prov->id)->get()->keyBy('tipo');
            $aprobados = $docs->where('estatus', 'aprobado')->count();
            $pendientes = $docs->where('estatus', 'pendiente')->count();
            $rechazados = $docs->where('estatus', 'rechazado')->count();
            $totalSubidos = $docs->count();

            // Determinar semáforo
            if ($aprobados >= 3 && $rechazados === 0) {
                $semaforo = 'verde';
            } elseif ($totalSubidos > 0 && $rechazados === 0) {
                $semaforo = 'amarillo';
            } elseif ($totalSubidos === 0) {
                $semaforo = 'gris';
            } else {
                $semaforo = 'rojo';
            }

            $documentosPorProveedor[] = [
                'proveedor'  => $prov,
                'docs'       => $docs,
                'aprobados'  => $aprobados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'total'      => $totalSubidos,
                'semaforo'   => $semaforo,
            ];
        }

        return view('admin.fiscal', compact('documentosPorProveedor', 'tiposRequeridos'));
    }

    // ── Materia Prima (Alejandra) ──

    public function materiaPrima()
    {
        $productos = Producto::where('activo', true)
            ->where('categoria', 'Materia prima')
            ->orderBy('stock', 'asc')
            ->get();

        $muestras = Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.materia-prima', compact('productos', 'muestras'));
    }

    // ── Material de Empaque (Rosy) ──

    public function materialEmpaque()
    {
        $productos = Producto::where('activo', true)
            ->whereIn('categoria', ['Consumible', 'Producto terminado'])
            ->orderBy('stock', 'asc')
            ->get();

        return view('admin.material-empaque', compact('productos'));
    }
}
