<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\OcBorrador;
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

        $proveedoresActivosList = ProveedorUser::where('activo', true)->get();
        $opinionActualizados = 0;
        foreach ($proveedoresActivosList as $prov) {
            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)
                ->where('tipo', 'opinion')
                ->latest()
                ->first();
            if ($doc && $doc->estatus === 'aprobado') {
                $opinionActualizados++;
            }
        }
        $totalOpinionProv = $proveedoresActivosList->count();
        $opinionPctActualizados = $totalOpinionProv > 0
            ? round(($opinionActualizados / $totalOpinionProv) * 100, 1) : 0;
        $opinionPctNoActualizados = $totalOpinionProv > 0
            ? round((($totalOpinionProv - $opinionActualizados) / $totalOpinionProv) * 100, 1) : 0;

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
            'conStock'           => Producto::where('stock', '>', 0)->count(),
            'opinionPctActualizados'    => $opinionPctActualizados,
            'opinionPctNoActualizados'  => $opinionPctNoActualizados,
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
        $tabActiva = $request->input('tab', 'proveedores');

        $filtrosProv = [
            'nombre' => $request->input('f_nombre'),
            'codigo' => $request->input('f_codigo'),
            'correo' => $request->input('f_correo'),
            'activo' => $request->input('f_activo'),
        ];
        $filtrosOc = [
            'proveedor' => $request->input('f_oc_proveedor'),
            'numero' => $request->input('f_oc_numero'),
            'producto' => $request->input('f_oc_producto'),
            'estatus' => $request->input('f_oc_estatus'),
            'fecha_desde' => $request->input('f_oc_fecha_desde'),
            'fecha_hasta' => $request->input('f_oc_fecha_hasta'),
            'vencida' => $request->input('f_oc_vencida'),
        ];
        $filtrosFact = [
            'folio' => $request->input('f_fact_folio'),
            'proveedor' => $request->input('f_fact_proveedor'),
            'vencidas' => $request->input('f_fact_vencidas'),
            'vence_desde' => $request->input('f_fact_vence_desde'),
            'vence_hasta' => $request->input('f_fact_vence_hasta'),
        ];

        $preserveProv = $this->filtrosAQuery($filtrosProv, [
            'nombre' => 'f_nombre',
            'codigo' => 'f_codigo',
            'correo' => 'f_correo',
            'activo' => 'f_activo',
        ]);
        $preserveOc = $this->filtrosAQuery($filtrosOc, [
            'proveedor' => 'f_oc_proveedor',
            'numero' => 'f_oc_numero',
            'producto' => 'f_oc_producto',
            'estatus' => 'f_oc_estatus',
            'fecha_desde' => 'f_oc_fecha_desde',
            'fecha_hasta' => 'f_oc_fecha_hasta',
            'vencida' => 'f_oc_vencida',
        ]);
        $preserveFact = $this->filtrosAQuery($filtrosFact, [
            'folio' => 'f_fact_folio',
            'proveedor' => 'f_fact_proveedor',
            'vencidas' => 'f_fact_vencidas',
            'vence_desde' => 'f_fact_vence_desde',
            'vence_hasta' => 'f_fact_vence_hasta',
        ]);

        $query = ProveedorUser::query();
        if ($filtrosProv['nombre']) {
            $query->where('nombre', 'like', '%'.$filtrosProv['nombre'].'%');
        }
        if ($filtrosProv['codigo']) {
            $query->where('codigo_compras', 'like', '%'.$filtrosProv['codigo'].'%');
        }
        if ($filtrosProv['correo']) {
            $query->where('correo', 'like', '%'.$filtrosProv['correo'].'%');
        }
        if ($filtrosProv['activo'] !== null && $filtrosProv['activo'] !== '') {
            $query->where('activo', $filtrosProv['activo'] === '1');
        }

        $proveedores = $query->orderBy('score_total', 'desc')->paginate(20)->withQueryString();
        $metricasProveedores = $this->buildProveedoresMetricas($proveedores->getCollection());

        $ordenesQuery = OcBorrador::with('proveedor')->orderByDesc('created_at');
        if ($filtrosOc['proveedor']) {
            $ordenesQuery->whereHas('proveedor', function ($pq) use ($filtrosOc) {
                $pq->where('nombre', 'like', '%'.$filtrosOc['proveedor'].'%')
                    ->orWhere('usuario', 'like', '%'.$filtrosOc['proveedor'].'%')
                    ->orWhere('codigo_compras', 'like', '%'.$filtrosOc['proveedor'].'%');
            });
        }
        if ($filtrosOc['numero']) {
            $ordenesQuery->where('id', ltrim($filtrosOc['numero'], '#'));
        }
        if ($filtrosOc['producto']) {
            $ordenesQuery->where('productos', 'like', '%'.$filtrosOc['producto'].'%');
        }
        if ($filtrosOc['estatus']) {
            $ordenesQuery->where('estatus', $filtrosOc['estatus']);
        }
        if ($filtrosOc['fecha_desde']) {
            $ordenesQuery->whereDate('created_at', '>=', $filtrosOc['fecha_desde']);
        }
        if ($filtrosOc['fecha_hasta']) {
            $ordenesQuery->whereDate('created_at', '<=', $filtrosOc['fecha_hasta']);
        }
        if ($filtrosOc['vencida'] === '1') {
            $ordenesQuery->where('estatus', '!=', 'completada')
                ->where('created_at', '<=', now()->subDays(30));
        }
        $ordenes = $ordenesQuery->limit(50)->get();

        $facturasQuery = Factura::where('estatus', 'pendiente')->whereNotNull('codigo_proveedor');
        if ($filtrosFact['folio']) {
            $facturasQuery->where('folio_cfdi', 'like', '%'.$filtrosFact['folio'].'%');
        }
        if ($filtrosFact['proveedor']) {
            $facturasQuery->where('codigo_proveedor', 'like', '%'.$filtrosFact['proveedor'].'%');
        }
        if ($filtrosFact['vencidas'] === '1') {
            $facturasQuery->where('fecha_vencimiento', '<', now());
        }
        if ($filtrosFact['vence_desde']) {
            $facturasQuery->whereDate('fecha_vencimiento', '>=', $filtrosFact['vence_desde']);
        }
        if ($filtrosFact['vence_hasta']) {
            $facturasQuery->whereDate('fecha_vencimiento', '<=', $filtrosFact['vence_hasta']);
        }
        $facturasPendientes = $facturasQuery->orderBy('fecha_vencimiento')->get();

        $estatusOc = ['pendiente', 'aprobada', 'en_proceso', 'completada'];
        $filtrosProvActivos = $this->filtrosTienenValor($filtrosProv);
        $filtrosOcActivos = $this->filtrosTienenValor($filtrosOc);
        $filtrosFactActivos = $this->filtrosTienenValor($filtrosFact);

        return view('admin.proveedores', compact(
            'proveedores',
            'filtrosProv',
            'filtrosOc',
            'filtrosFact',
            'preserveProv',
            'preserveOc',
            'preserveFact',
            'filtrosProvActivos',
            'filtrosOcActivos',
            'filtrosFactActivos',
            'estatusOc',
            'tabActiva',
            'ordenes',
            'facturasPendientes',
            'metricasProveedores'
        ));
    }

    // ── Detalle facturas de un proveedor ──

    public function proveedorFacturas(string $codigo)
    {
        $proveedor = ProveedorUser::where('codigo_compras', $codigo)->first();
        $facturas = Factura::where('codigo_proveedor', $codigo)->orderBy('fecha_vencimiento', 'desc')->get();

        // Exportar a Excel si se pide
        if (request('export') === 'excel') {
            $filename = "Facturas_{$codigo}_" . now()->format('Y-m-d') . '.csv';
            $lines = [];
            $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
            $lines[] = ['DETALLE DE ADEUDOS — ' . ($proveedor->nombre ?? $codigo) . ' (' . $codigo . ')'];
            $lines[] = ['Generado: ' . now()->format('d/m/Y H:i')];
            $lines[] = [];
            $lines[] = ['FOLIO CFDI', 'PRODUCTO', 'CODIGO PRODUCTO', 'TOTAL', 'VENCIMIENTO', 'DIAS VENCIDO', 'ESTATUS'];

            foreach ($facturas as $f) {
                $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                $diasV = $vencida ? $f->fecha_vencimiento->diffInDays(now()) : 0;
                $producto = null;
                if ($f->pedido_id) {
                    $pedido = Pedido::find($f->pedido_id);
                    if ($pedido && is_array($pedido->productos) && count($pedido->productos) > 0) {
                        $producto = $pedido->productos[0];
                    }
                }
                $lines[] = [
                    $f->folio_cfdi,
                    $producto['nombre'] ?? 'Compra general',
                    $producto['sku'] ?? $producto['codigo'] ?? '-',
                    number_format($f->total, 2),
                    $f->fecha_vencimiento?->format('d/m/Y') ?? '-',
                    $vencida ? $diasV . ' dias' : 'Vigente',
                    ucfirst($f->estatus),
                ];
            }
            $lines[] = [];
            $lines[] = ['', '', 'TOTAL DEUDA:', number_format($facturas->where('estatus', 'pendiente')->sum('total'), 2)];

            $handle = fopen('php://temp', 'r+');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($lines as $line) { fputcsv($handle, $line); }
            rewind($handle);
            $output = stream_get_contents($handle);
            fclose($handle);

            return response($output)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        return view('admin.proveedor-facturas', compact('proveedor', 'facturas', 'codigo'));
    }

    // ── Exportar facturas pendientes a Excel ──

    public function facturasPendientesExcel()
    {
        $facturas = Factura::where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->orderBy('codigo_proveedor')
            ->get();

        $productos = Producto::where('activo', true)->get();
        $filename = 'Facturas_Pendientes_Proveedores_' . now()->format('Y-m-d') . '.csv';

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['FACTURAS PENDIENTES DE PROVEEDORES CON PRODUCTOS'];
        $lines[] = ['Generado: ' . now()->format('d/m/Y H:i')];
        $lines[] = [];

        foreach ($facturas as $f) {
            $prov = ProveedorUser::where('codigo_compras', $f->codigo_proveedor)->first();
            $vencida = $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
            $diasV = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;

            // Header del proveedor
            $lines[] = ['PROVEEDOR:', $prov->nombre ?? $f->codigo_proveedor, 'CODIGO:', $f->codigo_proveedor, 'TOTAL ADEUDO:', '$' . number_format($f->total, 2), 'VENCIMIENTO:', $f->fecha_vencimiento?->format('d/m/Y') ?? '-', 'DIAS VENCIDO:', $vencida ? $diasV . ' dias' : 'Vigente'];
            $lines[] = ['COD. PRODUCTO', 'NOMBRE PRODUCTO', 'PRECIO', 'STOCK', 'UNIDAD'];

            // Productos del proveedor
            foreach ($productos as $prod) {
                $lines[] = [
                    $prod->codigo,
                    $prod->nombre,
                    '$' . number_format($prod->precio, 2),
                    number_format($prod->stock),
                    $prod->unidad_venta,
                ];
            }
            $lines[] = [];
        }

        $lines[] = ['TOTAL DEUDA GENERAL:', '', '$' . number_format($facturas->sum('total'), 2)];

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        foreach ($lines as $line) { fputcsv($handle, $line); }
        rewind($handle);
        $output = stream_get_contents($handle);
        fclose($handle);

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function filtrosAQuery(array $filtros, array $mapa): array
    {
        $query = [];
        foreach ($mapa as $key => $param) {
            $valor = $filtros[$key] ?? null;
            if ($valor !== null && $valor !== '') {
                $query[$param] = $valor;
            }
        }

        return $query;
    }

    private function filtrosTienenValor(array $filtros): bool
    {
        foreach ($filtros as $valor) {
            if ($valor !== null && $valor !== '') {
                return true;
            }
        }

        return false;
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
            'ventasTotales'          => Pedido::whereNotIn('estatus', ['cancelado'])->sum('total'),
            'deudasTotal'            => Factura::where('estatus', 'pendiente')->sum('total'),
            'deudasCount'            => Factura::where('estatus', 'pendiente')->count(),
            'facturasPagadas'        => Factura::where('estatus', 'pagada')->sum('total'),
            'pedidosPorMes'          => $this->pedidosPorMes(),
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

        $totalEncuestas   = Encuesta::count();
        $calificacionProm = round((float) Encuesta::avg('calificacion'), 1);
        $encuestas        = Encuesta::orderBy('created_at', 'desc')->limit(10)->get();

        return view('admin.otif', compact(
            'total', 'pagadas', 'pendientes', 'vencidas', 'canceladas',
            'otPercent', 'ifPercent', 'porcentaje', 'detalleProveedores',
            'totalEncuestas', 'calificacionProm', 'encuestas'
        ));
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

    // ── Opinión Positiva SAT por proveedor ──

    public function opinionPositiva()
    {
        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $opiniones = [];
        foreach ($proveedores as $prov) {
            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)
                ->where('tipo', 'opinion')
                ->latest()
                ->first();

            $opiniones[] = [
                'proveedor' => $prov,
                'documento' => $doc,
                'estatus'   => $doc ? $doc->estatus : 'sin_documento',
            ];
        }

        $aprobados = collect($opiniones)->where('estatus', 'aprobado')->count();
        $pendientes = collect($opiniones)->where('estatus', 'pendiente')->count();
        $rechazados = collect($opiniones)->where('estatus', 'rechazado')->count();
        $sinDoc = collect($opiniones)->where('estatus', 'sin_documento')->count();

        return view('admin.opinion-positiva', compact('opiniones', 'aprobados', 'pendientes', 'rechazados', 'sinDoc'));
    }

    // ── Gestión de Compras ──

    public function gestionCompras()
    {
        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();

        // Opinión positiva resumen
        $opinionData = [];
        foreach ($proveedores as $prov) {
            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)->where('tipo', 'opinion')->latest()->first();
            $opinionData[] = ['proveedor' => $prov, 'estatus' => $doc ? $doc->estatus : 'sin_documento'];
        }

        // Días de inventario por producto
        $inventarioDias = [];
        foreach ($productos as $prod) {
            $ventaMensual = Factura::whereNotNull('codigo_proveedor')
                ->where('created_at', '>=', now()->subMonths(3))
                ->count();
            $diasInventario = $prod->stock > 0 && $ventaMensual > 0
                ? round(($prod->stock / ($ventaMensual / 90)) * 1, 0)
                : 0;

            $inventarioDias[] = [
                'producto'        => $prod,
                'dias_inventario' => $diasInventario,
                'dias_pedido'     => 7,  // configurable después
                'dias_entrega'    => 5,  // configurable después
            ];
        }

        return view('admin.gestion-compras', compact('proveedores', 'productos', 'opinionData', 'inventarioDias'));
    }

    public function enviarAvisosOpinion()
    {
        $proveedores = ProveedorUser::where('activo', true)->get();
        $enviados = 0;

        foreach ($proveedores as $prov) {
            if (empty($prov->correo)) continue;

            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)->where('tipo', 'opinion')->latest()->first();
            $estatus = $doc ? $doc->estatus : 'sin_documento';

            if ($estatus === 'aprobado') continue;

            try {
                \Illuminate\Support\Facades\Mail::to($prov->correo)->send(
                    new \App\Mail\OpinionPositivaAviso($prov->nombre ?? $prov->usuario, $estatus)
                );
                $enviados++;
            } catch (\Exception $e) {
                // continuar con el siguiente
            }
        }

        return back()->with('mensaje', "Se enviaron {$enviados} correos de aviso de opinión positiva.");
    }

    public function autorizarProveedor(Request $request)
    {
        $request->validate(['proveedor_id' => 'required', 'accion' => 'required|in:alta,baja']);

        $prov = ProveedorUser::findOrFail($request->proveedor_id);
        $prov->update(['activo' => $request->accion === 'alta']);

        return back()->with('mensaje', "Proveedor {$prov->nombre} " . ($request->accion === 'alta' ? 'dado de alta' : 'dado de baja') . " por dirección.");
    }

    public function autorizarCosto(Request $request)
    {
        $request->validate(['producto_id' => 'required', 'nuevo_precio' => 'required|numeric|min:0']);

        $prod = Producto::findOrFail($request->producto_id);
        $precioAnterior = $prod->precio;
        $prod->update(['precio' => $request->nuevo_precio]);

        return back()->with('mensaje', "Costo de {$prod->nombre} actualizado: \${$precioAnterior} → \${$request->nuevo_precio} (autorizado por dirección).");
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
            ->orderBy('familia')
            ->orderBy('nombre')
            ->get();

        $productosPorFamilia = $productos->groupBy(fn($p) => $p->familia ?? 'Sin familia');

        $muestras = Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.materia-prima', compact('productos', 'productosPorFamilia', 'muestras'));
    }

    public function materiaPrimaCrear()
    {
        return view('admin.materia-prima-crear');
    }

    public function materiaPrimaGuardar(Request $request)
    {
        $request->validate([
            'codigo'  => 'required|string|unique:productos,codigo',
            'nombre'  => 'required|string|max:255',
        ]);

        Producto::create([
            'codigo'      => $request->codigo,
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'categoria'   => 'Materia prima',
            'familia'     => $request->familia ?? 'Sin familia',
            'precio'      => $request->precio ?? 0,
            'unidad_venta'=> $request->unidad_venta ?? 'kg',
            'stock'       => $request->stock ?? 0,
            'activo'      => !$request->has('inactivo'),
        ]);

        return redirect()->route('admin.materia-prima')->with('mensaje', 'Producto registrado: ' . $request->codigo);
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

    private function buildProveedoresMetricas($proveedores): array
    {
        $codigos = $proveedores->pluck('codigo_compras')->filter()->values();
        if ($codigos->isEmpty()) {
            return [];
        }

        $trimInicio = now()->subMonths(3);
        $facturas = Factura::whereIn('codigo_proveedor', $codigos)
            ->where('created_at', '>=', now()->subMonths(6))
            ->get()
            ->groupBy('codigo_proveedor');

        $metricas = [];
        foreach ($proveedores as $prov) {
            $codigo = $prov->codigo_compras;
            if (! $codigo) {
                $metricas[$prov->id] = $this->metricasProveedorVacias($prov);
                continue;
            }

            $grupo = $facturas->get($codigo, collect());
            $actual = $grupo->filter(fn ($f) => $f->created_at >= $trimInicio);
            $anterior = $grupo->filter(fn ($f) => $f->created_at < $trimInicio);

            $otifActual = $this->pctOtifFromFacturas($actual);
            $otifAnterior = $this->pctOtifFromFacturas($anterior);
            $entregaActual = $this->pctEntregaFromFacturas($actual);
            $entregaAnterior = $this->pctEntregaFromFacturas($anterior);
            $puntualidadActual = $this->pctPuntualidadFromFacturas($actual);
            $puntualidadAnterior = $this->pctPuntualidadFromFacturas($anterior);

            $comprasTrim = (float) $actual->sum('total');
            $comprasAnterior = (float) $anterior->sum('total');
            $forecast = min(100, max(0, (float) $prov->score_total * 1.1));
            $forecastAnterior = min(100, max(0, $otifAnterior * 1.1));

            $metricas[$prov->id] = [
                'forecast' => $forecast,
                'compras_trim' => $comprasTrim,
                'estimado' => $comprasTrim > 0 ? round($comprasTrim / 3, 2) : 0,
                'score_class' => $this->scoreBarClass((float) $prov->score_total),
                'forecast_class' => $this->scoreBarClass($forecast),
                'trend_otif' => $this->deltaTrend($otifActual, $otifAnterior),
                'trend_entrega' => $this->deltaTrend($entregaActual, $entregaAnterior),
                'trend_puntualidad' => $this->deltaTrend($puntualidadActual, $puntualidadAnterior),
                'trend_forecast' => $this->deltaTrend($forecast, $forecastAnterior),
                'trend_compras' => $this->pctCambio($comprasTrim, $comprasAnterior),
            ];
        }

        return $metricas;
    }

    private function metricasProveedorVacias(ProveedorUser $prov): array
    {
        $forecast = min(100, max(0, (float) $prov->score_total * 1.1));

        return [
            'forecast' => $forecast,
            'compras_trim' => 0,
            'estimado' => 0,
            'score_class' => $this->scoreBarClass((float) $prov->score_total),
            'forecast_class' => $this->scoreBarClass($forecast),
            'trend_otif' => 0,
            'trend_entrega' => 0,
            'trend_puntualidad' => 0,
            'trend_forecast' => 0,
            'trend_compras' => 0,
        ];
    }

    private function pctOtifFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        return round($facturas->where('estatus', 'pagada')->count() / $total * 100, 1);
    }

    private function pctEntregaFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        return round($facturas->where('estatus', '!=', 'cancelada')->count() / $total * 100, 1);
    }

    private function pctPuntualidadFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        $puntuales = $facturas->filter(function ($f) {
            if ($f->estatus === 'cancelada') {
                return false;
            }
            if ($f->estatus === 'pagada') {
                return ! $f->fecha_vencimiento || ! $f->fecha_vencimiento->isPast();
            }

            return $f->estatus === 'pendiente'
                && $f->fecha_vencimiento
                && $f->fecha_vencimiento->isFuture();
        })->count();

        return round($puntuales / $total * 100, 1);
    }

    private function deltaTrend(float $actual, float $anterior): int
    {
        return (int) round($actual - $anterior);
    }

    private function pctCambio(float $actual, float $anterior): int
    {
        if ($anterior <= 0) {
            return $actual > 0 ? 100 : 0;
        }

        return (int) round((($actual - $anterior) / $anterior) * 100);
    }

    private function scoreBarClass(float $pct): string
    {
        return $pct >= 70 ? 'score-high' : ($pct >= 40 ? 'score-mid' : 'score-low');
    }
}
