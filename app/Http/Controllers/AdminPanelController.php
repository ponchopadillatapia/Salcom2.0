<?php

namespace App\Http\Controllers;

use App\Mail\OpinionPositivaAviso;
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
use Illuminate\Support\Facades\Mail;

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

        $inicioMes = now()->startOfMonth();
        $inicioMesAnterior = now()->subMonth()->startOfMonth();
        $finMesAnterior = now()->subMonth()->endOfMonth();

        $productosActivos = Producto::where('activo', true)->count();
        $sinStock = Producto::where('activo', true)->where('stock', '<=', 0)->count();
        $stockBajo = Producto::where('activo', true)->where('stock', '>', 0)->where('stock', '<', 50)->count();
        $stockOk = Producto::where('activo', true)->where('stock', '>=', 50)->count();
        $conStock = Producto::where('activo', true)->where('stock', '>', 0)->count();
        $totalStock = (int) Producto::where('activo', true)->sum('stock');
        // Salud: OK=100%, bajo=50%, agotado=0% (no confundir con "tiene stock > 0")
        $saludPct = $productosActivos > 0
            ? round((($stockOk * 100) + ($stockBajo * 50)) / $productosActivos)
            : 0;

        $activosMesAnterior = Producto::where('activo', true)
            ->where('created_at', '<', $inicioMes)
            ->count();
        $skusVarPct = $activosMesAnterior > 0
            ? round((($productosActivos - $activosMesAnterior) / $activosMesAnterior) * 100)
            : 0;

        $agotadosMesActual = Producto::where('activo', true)
            ->where('stock', '<=', 0)
            ->where('updated_at', '>=', $inicioMes)
            ->count();
        $agotadosMesAnterior = Producto::where('activo', true)
            ->where('stock', '<=', 0)
            ->whereBetween('updated_at', [$inicioMesAnterior, $finMesAnterior])
            ->count();
        $agotadosVarPct = $agotadosMesAnterior > 0
            ? round((($agotadosMesActual - $agotadosMesAnterior) / $agotadosMesAnterior) * 100)
            : 0;

        $proveedoresOtif = ProveedorUser::where('activo', true);
        $otPromedio = round((float) $proveedoresOtif->avg('score_entrega'), 0);
        $ifPromedio = round((float) $proveedoresOtif->avg('score_puntualidad'), 0);

        $data = [
            'totalProveedores' => ProveedorUser::count(),
            'proveedoresActivos' => ProveedorUser::where('activo', true)->count(),
            'scorePromedio' => round((float) ProveedorUser::avg('score_total'), 1),
            'totalPedidos' => Pedido::count(),
            'pedidosPendientes' => Pedido::whereIn('estatus', ['validacion', 'procesando'])->count(),
            'pedidosEntregados' => Pedido::where('estatus', 'entregado')->count(),
            'montoPedidos' => Pedido::sum('total'),
            'totalProductos' => $productosActivos,
            'sinStock' => $sinStock,
            'stockBajo' => $stockBajo,
            'stockOk' => $stockOk,
            'conStock' => $conStock,
            'totalStock' => $totalStock,
            'saludPct' => $saludPct,
            'skusVarPct' => $skusVarPct,
            'agotadosVarPct' => $agotadosVarPct,
            'opinionPctActualizados' => $opinionPctActualizados,
            'opinionPctNoActualizados' => $opinionPctNoActualizados,
            'facturasPendientes' => Factura::where('estatus', 'pendiente')->count(),
            'montoFacturas' => Factura::where('estatus', 'pendiente')->sum('total'),
            'docsPendientes' => DocumentoProveedor::where('estatus', 'pendiente')->count(),
            'ultimosPedidos' => Pedido::orderBy('created_at', 'desc')->limit(3)->get(),
            'topProveedores' => ProveedorUser::where('score_total', '>', 0)->orderBy('score_total', 'desc')->limit(3)->get(),
            'pedidosPorMes' => $pedidosPorMes,
            'facturasPorEstatus' => $facturasPorEstatus,
            'otPromedio' => $otPromedio,
            'ifPromedio' => $ifPromedio,
        ];

        return view('admin.dashboard', $data);
    }

    // ── Lista de Clientes ──

    public function clientes(Request $request)
    {
        $tipoOpciones = [
            'mayorista' => 'Mayorista',
            'minorista' => 'Minorista',
            'distribuidor' => 'Distribuidor',
        ];

        $query = ClienteUser::query();

        $busqueda = $request->input('busqueda', '');
        $activo = $request->input('activo', '');
        $tipoCliente = $request->input('tipo_cliente', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_cliente', 'like', "%{$busqueda}%")
                    ->orWhere('usuario', 'like', "%{$busqueda}%");
            });
        }

        if ($activo !== '') {
            $query->where('activo', $activo === '1');
        }

        if ($tipoCliente && array_key_exists($tipoCliente, $tipoOpciones)) {
            $query->where('tipo_cliente', $tipoCliente);
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $clientes = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $totalGeneral = ClienteUser::count();
        $conteoActivos = ClienteUser::where('activo', true)->count();
        $conteoInactivos = ClienteUser::where('activo', false)->count();

        $conteosTipo = ClienteUser::selectRaw('tipo_cliente, count(*) as total')
            ->whereNotNull('tipo_cliente')
            ->where('tipo_cliente', '!=', '')
            ->groupBy('tipo_cliente')
            ->pluck('total', 'tipo_cliente');

        $filtros = [
            'busqueda' => $busqueda,
            'activo' => $activo,
            'tipo_cliente' => $tipoCliente,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        return view('admin.clientes', compact(
            'clientes',
            'tipoOpciones',
            'totalGeneral',
            'conteoActivos',
            'conteoInactivos',
            'conteosTipo',
            'filtros',
            'filtrosActivos',
        ));
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
        $estatusOpciones = [
            'validacion' => 'En validación',
            'procesando' => 'En proceso',
            'enviado' => 'Enviado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
        ];

        $query = Pedido::query();

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('folio', 'like', "%{$busqueda}%")
                    ->orWhere('nombre_cliente', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_cliente', 'like', "%{$busqueda}%");
            });
        }

        $estatus = $request->input('estatus');
        $grupo = $request->input('grupo');

        if ($grupo === 'pendientes') {
            $query->whereIn('estatus', ['validacion', 'procesando']);
        } elseif ($grupo === 'activos') {
            $query->whereIn('estatus', ['validacion', 'procesando', 'enviado']);
        } elseif ($estatus && array_key_exists($estatus, $estatusOpciones)) {
            $query->where('estatus', $estatus);
        }

        if ($tipoPago = $request->input('tipo_pago')) {
            $query->where('tipo_pago', $tipoPago);
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $conteosEstatus = Pedido::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');

        $totalGeneral = Pedido::count();
        $conteoPendientes = ($conteosEstatus['validacion'] ?? 0) + ($conteosEstatus['procesando'] ?? 0);

        $filtros = [
            'busqueda' => $busqueda ?? '',
            'estatus' => $estatus ?? '',
            'grupo' => $grupo ?? '',
            'tipo_pago' => $tipoPago ?? '',
            'fecha_desde' => $fechaDesde ?? '',
            'fecha_hasta' => $fechaHasta ?? '',
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        return view('admin.pedidos', compact(
            'pedidos',
            'estatus',
            'grupo',
            'estatusOpciones',
            'conteosEstatus',
            'conteoPendientes',
            'totalGeneral',
            'filtros',
            'filtrosActivos',
        ));
    }

    // ── Proveedores con Score ──

    public function proveedores(Request $request)
    {
        $tabActiva = $request->input('tab', 'proveedores');

        $filtrosProv = [
            'busqueda' => $request->input('busqueda', ''),
            'nombre' => $request->input('f_nombre'),
            'codigo' => $request->input('f_codigo'),
            'correo' => $request->input('f_correo'),
            'activo' => $request->input('f_activo', ''),
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
            'busqueda' => 'busqueda',
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
        if ($filtrosProv['busqueda']) {
            $b = $filtrosProv['busqueda'];
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'like', "%{$b}%")
                    ->orWhere('codigo_compras', 'like', "%{$b}%")
                    ->orWhere('correo', 'like', "%{$b}%")
                    ->orWhere('usuario', 'like', "%{$b}%");
            });
        } else {
            if ($filtrosProv['nombre']) {
                $query->where('nombre', 'like', '%'.$filtrosProv['nombre'].'%');
            }
            if ($filtrosProv['codigo']) {
                $query->where('codigo_compras', 'like', '%'.$filtrosProv['codigo'].'%');
            }
            if ($filtrosProv['correo']) {
                $query->where('correo', 'like', '%'.$filtrosProv['correo'].'%');
            }
        }
        if ($filtrosProv['activo'] !== '') {
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
        $estatusOcLabels = [
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'en_proceso' => 'En proceso',
            'completada' => 'Completada',
        ];

        $totalProveedores = ProveedorUser::count();
        $conteoActivos = ProveedorUser::where('activo', true)->count();
        $conteoInactivos = ProveedorUser::where('activo', false)->count();
        $conteosOcEstatus = OcBorrador::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');
        $totalOrdenes = OcBorrador::count();
        $conteoOcVencidas = OcBorrador::where('estatus', '!=', 'completada')
            ->where('created_at', '<=', now()->subDays(30))
            ->count();
        $conteoFacturasPendientes = Factura::where('estatus', 'pendiente')->whereNotNull('codigo_proveedor')->count();
        $conteoFacturasVencidas = Factura::where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->where('fecha_vencimiento', '<', now())
            ->count();

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
            'estatusOcLabels',
            'tabActiva',
            'ordenes',
            'facturasPendientes',
            'metricasProveedores',
            'totalProveedores',
            'conteoActivos',
            'conteoInactivos',
            'conteosOcEstatus',
            'totalOrdenes',
            'conteoOcVencidas',
            'conteoFacturasPendientes',
            'conteoFacturasVencidas',
        ));
    }

    // ── Detalle facturas de un proveedor ──

    public function proveedorFacturas(string $codigo)
    {
        $proveedor = ProveedorUser::where('codigo_compras', $codigo)->first();
        $facturas = Factura::where('codigo_proveedor', $codigo)->orderBy('fecha_vencimiento', 'desc')->get();

        // Exportar a Excel si se pide
        if (request('export') === 'excel') {
            $filename = "Facturas_{$codigo}_".now()->format('Y-m-d').'.csv';
            $lines = [];
            $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
            $lines[] = ['DETALLE DE ADEUDOS — '.($proveedor->nombre ?? $codigo).' ('.$codigo.')'];
            $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
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
                    number_format((float) $f->total, 2),
                    $f->fecha_vencimiento?->format('d/m/Y') ?? '-',
                    $vencida ? $diasV.' dias' : 'Vigente',
                    ucfirst($f->estatus),
                ];
            }
            $lines[] = [];
            $lines[] = ['', '', 'TOTAL DEUDA:', number_format((float) $facturas->where('estatus', 'pendiente')->sum('total'), 2)];

            $handle = fopen('php://temp', 'r+');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
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

        return view('admin.proveedor-facturas', compact('proveedor', 'facturas', 'codigo'));
    }

    // ── Exportar facturas pendientes a Excel ──

    public function facturasPendientesExcel()
    {
        $facturas = Factura::where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->orderBy('codigo_proveedor')
            ->get();

        $filename = 'Facturas_Pendientes_Proveedores_'.now()->format('Y-m-d').'.csv';

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['FACTURAS PENDIENTES DE PROVEEDORES'];
        $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
        $lines[] = [];
        $lines[] = ['PROVEEDOR', 'FOLIO CFDI', 'MONTO', 'IVA', 'TOTAL', 'VENCIMIENTO', 'ESTADO'];

        foreach ($facturas as $f) {
            $prov = ProveedorUser::where('codigo_compras', $f->codigo_proveedor)->first();
            $vencida = $f->fecha_vencimiento && $f->fecha_vencimiento->isPast() ? 'VENCIDA' : 'Vigente';
            $lines[] = [
                $prov->nombre ?? $f->codigo_proveedor,
                $f->folio_cfdi,
                number_format((float) $f->monto, 2),
                number_format((float) $f->monto_iva, 2),
                number_format((float) $f->total, 2),
                $f->fecha_vencimiento?->format('d/m/Y') ?? '—',
                $vencida,
            ];
        }

        $lines[] = [];
        $lines[] = ['', '', '', 'TOTAL:', number_format((float) $facturas->sum('total'), 2)];

        $handle = fopen('php://temp', 'r+');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
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
        $stockOpciones = [
            'agotado' => 'Agotado',
            'bajo' => 'Stock bajo',
            'ok' => 'Stock OK',
        ];

        $query = Producto::query();

        $busqueda = $request->input('busqueda', '');
        $stock = $request->input('stock', '');
        $grupo = $request->input('grupo', '');
        $activo = $request->input('activo', '');
        $categoria = $request->input('categoria', '');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('codigo', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_alterno', 'like', "%{$busqueda}%")
                    ->orWhere('categoria', 'like', "%{$busqueda}%");
            });
        }

        if ($request->input('sin_stock')) {
            $stock = 'agotado';
        }

        if ($grupo === 'criticos') {
            $query->where(function ($q) {
                $q->where('stock', '<=', 0)
                    ->orWhere(function ($q2) {
                        $q2->where('stock', '>', 0)->where('stock', '<', 50);
                    });
            });
        } elseif ($stock && array_key_exists($stock, $stockOpciones)) {
            if ($stock === 'agotado') {
                $query->where('stock', '<=', 0);
            } elseif ($stock === 'bajo') {
                $query->where('stock', '>', 0)->where('stock', '<', 50);
            } elseif ($stock === 'ok') {
                $query->where('stock', '>=', 50);
            }
        }

        if ($activo !== '') {
            $query->where('activo', $activo === '1');
        }

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $productos = $query->orderBy('codigo')->paginate(20)->withQueryString();

        $totalGeneral = Producto::count();
        $conteoAgotado = Producto::where('stock', '<=', 0)->count();
        $conteoBajo = Producto::where('stock', '>', 0)->where('stock', '<', 50)->count();
        $conteoOk = Producto::where('stock', '>=', 50)->count();
        $conteoCriticos = $conteoAgotado + $conteoBajo;
        $conteoInactivos = Producto::where('activo', false)->count();

        $categorias = Producto::whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        $filtros = [
            'busqueda' => $busqueda,
            'stock' => $stock,
            'grupo' => $grupo,
            'activo' => $activo,
            'categoria' => $categoria,
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        return view('admin.productos', compact(
            'productos',
            'stockOpciones',
            'stock',
            'grupo',
            'totalGeneral',
            'conteoAgotado',
            'conteoBajo',
            'conteoOk',
            'conteoCriticos',
            'conteoInactivos',
            'categorias',
            'filtros',
            'filtrosActivos',
        ));
    }

    // ── Facturas ──

    public function facturas(Request $request)
    {
        $estatusOpciones = [
            'pendiente' => 'Pendiente',
            'pagada' => 'Pagada',
            'cancelada' => 'Cancelada',
        ];

        $query = Factura::with('proveedor')->whereNotNull('codigo_proveedor');

        $busqueda = $request->input('busqueda', '');
        $estatus = $request->input('estatus', '');
        $vencidas = $request->input('vencidas', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('folio_cfdi', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_proveedor', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', function ($q2) use ($busqueda) {
                        $q2->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('codigo_compras', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($vencidas) {
            $query->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        } elseif ($estatus && array_key_exists($estatus, $estatusOpciones)) {
            $query->where('estatus', $estatus);
        }

        if ($fechaDesde) {
            $query->whereDate('fecha_vencimiento', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha_vencimiento', '<=', $fechaHasta);
        }

        $facturas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $baseProveedor = Factura::whereNotNull('codigo_proveedor');
        $totalGeneral = (clone $baseProveedor)->count();
        $conteosEstatus = (clone $baseProveedor)->selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');
        $conteoVencidas = (clone $baseProveedor)
            ->where('estatus', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->count();

        $filtros = [
            'busqueda' => $busqueda,
            'estatus' => $estatus,
            'vencidas' => $vencidas,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        return view('admin.facturas', compact(
            'facturas',
            'estatus',
            'vencidas',
            'estatusOpciones',
            'totalGeneral',
            'conteosEstatus',
            'conteoVencidas',
            'filtros',
            'filtrosActivos',
        ));
    }

    // ── Documentos de proveedores ──

    public function documentos(Request $request)
    {
        $estatusOpciones = [
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
        ];

        $tipoLabels = [
            'cif' => 'CIF',
            'opinion' => 'Opinión positiva',
            'caratula_banco' => 'Carátula banco',
            'acta_constitutiva' => 'Acta constitutiva',
            'comprobante_domicilio' => 'Comprobante domicilio',
        ];

        $query = DocumentoProveedor::with('proveedor');

        $busqueda = $request->input('busqueda', '');
        $estatus = $request->input('estatus', '');
        $tipo = $request->input('tipo', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('tipo', 'like', "%{$busqueda}%")
                    ->orWhere('notas_revision', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', function ($q2) use ($busqueda) {
                        $q2->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('codigo_compras', 'like', "%{$busqueda}%")
                            ->orWhere('usuario', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($estatus && array_key_exists($estatus, $estatusOpciones)) {
            $query->where('estatus', $estatus);
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $documentos = $query
            ->orderByRaw("CASE estatus WHEN 'pendiente' THEN 1 WHEN 'rechazado' THEN 2 WHEN 'aprobado' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $totalGeneral = DocumentoProveedor::count();
        $conteosEstatus = DocumentoProveedor::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');

        $tipos = DocumentoProveedor::select('tipo')
            ->distinct()
            ->orderBy('tipo')
            ->pluck('tipo');

        $filtros = [
            'busqueda' => $busqueda,
            'estatus' => $estatus,
            'tipo' => $tipo,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        return view('admin.documentos', compact(
            'documentos',
            'estatus',
            'estatusOpciones',
            'tipoLabels',
            'tipos',
            'totalGeneral',
            'conteosEstatus',
            'filtros',
            'filtrosActivos',
        ));
    }

    // ── Negocio ──

    public function negocio()
    {
        $data = [
            'ventasTotales' => Pedido::whereNotIn('estatus', ['cancelado'])->sum('total'),
            'deudasTotal' => Factura::where('estatus', 'pendiente')->sum('total'),
            'deudasCount' => Factura::where('estatus', 'pendiente')->count(),
            'facturasPagadas' => Factura::where('estatus', 'pagada')->sum('total'),
            'pedidosPorMes' => $this->pedidosPorMes(),
        ];

        return view('admin.negocio', $data);
    }

    public function otif()
    {
        return redirect()->route('admin.encuestas');
    }

    // ── Inventario ──

    public function inventario()
    {
        $productos = Producto::where('activo', true)->orderBy('stock', 'asc')->get();
        $totalStock = Producto::where('activo', true)->sum('stock');
        $sinStock = Producto::where('activo', true)->where('stock', '<=', 0)->count();
        $stockBajo = Producto::where('activo', true)->where('stock', '>', 0)->where('stock', '<', 50)->count();
        $stockOk = Producto::where('activo', true)->where('stock', '>=', 50)->count();

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
                'mes' => $mesesNombres[(int) $fecha->format('n')].' '.$fecha->format('Y'),
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
        $metricasProveedores = $this->buildProveedoresMetricas($proveedores);

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

            $otifAnterior = $this->pctOtifFromFacturas($facturasAnterior);
            $otifActual = $this->pctOtifFromFacturas($facturasActual);

            $reporte[] = [
                'codigo' => $prov->codigo_compras,
                'nombre' => $prov->nombre ?? $prov->usuario,
                'compras_anterior' => $montoAnterior,
                'compras_actual' => $montoActual,
                'facturas_anterior' => $cantAnterior,
                'facturas_actual' => $cantActual,
                'variacion_monto' => $variacionMonto,
                'variacion_cant' => $variacionCant,
                'otif' => $this->otifCompuesto((float) $prov->score_entrega, (float) $prov->score_puntualidad),
                'trend_otif' => $this->trendOtifReporte(
                    $prov,
                    $otifActual,
                    $otifAnterior,
                    $cantActual,
                    $cantAnterior,
                    $metricasProveedores
                ),
            ];

            $totales['compras_anterior'] += $montoAnterior;
            $totales['compras_actual'] += $montoActual;
            $totales['facturas_anterior'] += $cantAnterior;
            $totales['facturas_actual'] += $cantActual;
        }

        $totales['variacion_monto'] = $totales['compras_anterior'] > 0
            ? round((($totales['compras_actual'] - $totales['compras_anterior']) / $totales['compras_anterior']) * 100, 1)
            : ($totales['compras_actual'] > 0 ? 100 : 0);
        $totales['variacion_cant'] = $totales['facturas_anterior'] > 0
            ? round((($totales['facturas_actual'] - $totales['facturas_anterior']) / $totales['facturas_anterior']) * 100, 1)
            : ($totales['facturas_actual'] > 0 ? 100 : 0);

        $facturasTotalesAnterior = Factura::whereNotNull('codigo_proveedor')->whereYear('created_at', $anioAnterior)->get();
        $facturasTotalesActual = Factura::whereNotNull('codigo_proveedor')->whereYear('created_at', $anioActual)->get();
        $otifTotalAnterior = $this->pctOtifFromFacturas($facturasTotalesAnterior);
        $otifTotalActual = $this->pctOtifFromFacturas($facturasTotalesActual);
        $totales['otif'] = round($proveedores->avg(fn ($p) => $this->otifCompuesto(
            (float) $p->score_entrega,
            (float) $p->score_puntualidad
        )), 1);
        $totales['trend_otif'] = $this->trendOtifReporte(
            null,
            $otifTotalActual,
            $otifTotalAnterior,
            $facturasTotalesActual->count(),
            $facturasTotalesAnterior->count(),
            [],
            $totales['otif']
        );

        return view('admin.reporte-proveedores', compact('reporte', 'totales', 'anioActual', 'anioAnterior'));
    }

    public function reporteProveedoresExcel()
    {
        $anioActual = (int) date('Y');
        $anioAnterior = $anioActual - 1;

        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();
        $metricasProveedores = $this->buildProveedoresMetricas($proveedores);

        $filename = 'Reporte_Proveedores_'.$anioActual.'.csv';

        // Generar CSV en memoria
        $output = chr(0xEF).chr(0xBB).chr(0xBF); // BOM UTF-8

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['REPORTE DE COMPRAS POR PROVEEDOR - COMPARATIVO ANUAL'];
        $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
        $lines[] = [];
        $lines[] = ['CODIGO', 'PROVEEDOR', 'OTIF %', 'VAR OTIF', "FACTURAS {$anioAnterior}", "FACTURAS {$anioActual}", 'VAR FACTURAS %', "COMPRAS {$anioAnterior}", "COMPRAS {$anioActual}", 'VAR COMPRAS %'];

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

            $otifAnt = $this->pctOtifFromFacturas($facturasAnt);
            $otifAct = $this->pctOtifFromFacturas($facturasAct);
            $varOtif = $this->trendOtifReporte($prov, $otifAct, $otifAnt, $cantAct, $cantAnt, $metricasProveedores);

            $lines[] = [
                $prov->codigo_compras,
                $prov->nombre ?? $prov->usuario,
                number_format($this->otifCompuesto((float) $prov->score_entrega, (float) $prov->score_puntualidad), 0).'%',
                ($varOtif > 0 ? '+' : '').$varOtif.'%',
                $cantAnt,
                $cantAct,
                $varCant.'%',
                number_format($montoAnt, 2),
                number_format($montoAct, 2),
                $varMonto.'%',
            ];

            $totalAnterior += $montoAnt;
            $totalActual += $montoAct;
        }

        $lines[] = [];
        $varTotal = $totalAnterior > 0 ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1) : 0;
        $facturasTotAnt = Factura::whereNotNull('codigo_proveedor')->whereYear('created_at', $anioAnterior)->get();
        $facturasTotAct = Factura::whereNotNull('codigo_proveedor')->whereYear('created_at', $anioActual)->get();
        $otifTotAnt = $this->pctOtifFromFacturas($facturasTotAnt);
        $otifTotAct = $this->pctOtifFromFacturas($facturasTotAct);
        $avgScore = round($proveedores->avg(fn ($p) => $this->otifCompuesto(
            (float) $p->score_entrega,
            (float) $p->score_puntualidad
        )), 1);
        $varOtifTotal = $this->trendOtifReporte(
            null,
            $otifTotAct,
            $otifTotAnt,
            $facturasTotAct->count(),
            $facturasTotAnt->count(),
            [],
            $avgScore
        );

        $lines[] = [
            '', 'GRAN TOTAL',
            number_format($avgScore, 0).'%',
            ($varOtifTotal > 0 ? '+' : '').$varOtifTotal.'%',
            '', '', '', number_format($totalAnterior, 2), number_format($totalActual, 2), $varTotal.'%',
        ];

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
                'meses' => [],
                'total' => 0,
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
        $lines[] = ['CORTE DE COMPRAS POR PROVEEDOR - ENERO A '.strtoupper($this->mesNombre($mesActual))." {$anio}"];
        $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
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
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
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
                'estatus' => $doc ? $doc->estatus : 'sin_documento',
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
                'producto' => $prod,
                'dias_inventario' => $diasInventario,
                'dias_pedido' => 7,  // configurable después
                'dias_entrega' => 5,  // configurable después
            ];
        }

        return view('admin.gestion-compras', compact('proveedores', 'productos', 'opinionData', 'inventarioDias'));
    }

    public function enviarAvisosOpinion()
    {
        $proveedores = ProveedorUser::where('activo', true)->get();
        $enviados = 0;

        foreach ($proveedores as $prov) {
            if (empty($prov->correo)) {
                continue;
            }

            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)->where('tipo', 'opinion')->latest()->first();
            $estatus = $doc ? $doc->estatus : 'sin_documento';

            if ($estatus === 'aprobado') {
                continue;
            }

            try {
                Mail::to($prov->correo)->send(
                    new OpinionPositivaAviso($prov->nombre ?? $prov->usuario, $estatus)
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

        return back()->with('mensaje', "Proveedor {$prov->nombre} ".($request->accion === 'alta' ? 'dado de alta' : 'dado de baja').' por dirección.');
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
                'proveedor' => $prov,
                'docs' => $docs,
                'aprobados' => $aprobados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'total' => $totalSubidos,
                'semaforo' => $semaforo,
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

        $productosPorFamilia = $productos->groupBy(fn ($p) => $p->familia ?? 'Sin familia');

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
            'codigo' => 'required|string|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
        ]);

        Producto::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'categoria' => 'Materia prima',
            'familia' => $request->familia ?? 'Sin familia',
            'precio' => $request->precio ?? 0,
            'unidad_venta' => $request->unidad_venta ?? 'kg',
            'stock' => $request->stock ?? 0,
            'activo' => ! $request->has('inactivo'),
        ]);

        return redirect()->route('admin.materia-prima')->with('mensaje', 'Producto registrado: '.$request->codigo);
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

            $otifFactActual = $this->pctOtifFromFacturas($actual);
            $otifFactAnterior = $this->pctOtifFromFacturas($anterior);
            $entregaFactActual = $this->pctEntregaFromFacturas($actual);
            $entregaFactAnterior = $this->pctEntregaFromFacturas($anterior);
            $puntualidadFactActual = $this->pctPuntualidadFromFacturas($actual);
            $puntualidadFactAnterior = $this->pctPuntualidadFromFacturas($anterior);

            $cantActual = $actual->count();
            $cantAnterior = $anterior->count();
            [$entrega, $puntualidad] = $this->scoresEntregaPuntualidad($prov, $actual);
            $otif = $this->otifCompuesto($entrega, $puntualidad);

            $comprasTrim = (float) $actual->sum('total');
            $comprasAnterior = (float) $anterior->sum('total');
            $forecast = min(100, max(0, $otif * 1.1));
            $forecastAnterior = min(100, max(0, $otifFactAnterior * 1.1));

            $metricas[$prov->id] = [
                'otif' => $otif,
                'entrega' => $entrega,
                'puntualidad' => $puntualidad,
                'forecast' => $forecast,
                'compras_trim' => $comprasTrim,
                'estimado' => $comprasTrim > 0 ? round($comprasTrim / 3, 2) : 0,
                'score_class' => $this->scoreBarClass($otif),
                'forecast_class' => $this->scoreBarClass($forecast),
                'trend_otif' => $this->trendOtifProveedor(
                    $otif,
                    $this->otifCompuesto($entregaFactActual, $puntualidadFactActual),
                    $this->otifCompuesto($entregaFactAnterior, $puntualidadFactAnterior),
                    $cantActual,
                    $cantAnterior
                ),
                'trend_entrega' => $this->trendMetricaProveedor($entrega, $entregaFactActual, $entregaFactAnterior, $cantActual, $cantAnterior),
                'trend_puntualidad' => $this->trendMetricaProveedor($puntualidad, $puntualidadFactActual, $puntualidadFactAnterior, $cantActual, $cantAnterior),
                'trend_forecast' => $this->variacionPctOtif($forecast, $forecastAnterior),
                'trend_compras' => $this->pctCambio($comprasTrim, $comprasAnterior),
            ];
        }

        return $metricas;
    }

    private function metricasProveedorVacias(ProveedorUser $prov): array
    {
        [$entrega, $puntualidad] = $this->scoresEntregaPuntualidad($prov, collect());
        $otif = $this->otifCompuesto($entrega, $puntualidad);
        $forecast = min(100, max(0, $otif * 1.1));

        return [
            'otif' => $otif,
            'entrega' => $entrega,
            'puntualidad' => $puntualidad,
            'forecast' => $forecast,
            'compras_trim' => 0,
            'estimado' => 0,
            'score_class' => $this->scoreBarClass($otif),
            'forecast_class' => $this->scoreBarClass($forecast),
            'trend_otif' => round($otif - 60, 1),
            'trend_entrega' => round($entrega - 80, 1),
            'trend_puntualidad' => round($puntualidad - 80, 1),
            'trend_forecast' => 0,
            'trend_compras' => 0,
        ];
    }

    /**
     * Entrega (IF) y puntualidad (OT) del trimestre actual (facturas) o scores guardados.
     *
     * @return array{0: float, 1: float}
     */
    private function scoresEntregaPuntualidad(ProveedorUser $prov, $facturasTrimestre): array
    {
        if ($facturasTrimestre->count() > 0) {
            return [
                $this->pctEntregaFromFacturas($facturasTrimestre),
                $this->pctPuntualidadFromFacturas($facturasTrimestre),
            ];
        }

        return [(float) $prov->score_entrega, (float) $prov->score_puntualidad];
    }

    /** OTIF compuesto: 50% entrega + 50% puntualidad (igual que ProveedorUser::recalcularScore). */
    private function otifCompuesto(float $entrega, float $puntualidad): float
    {
        return round(($entrega * 0.5) + ($puntualidad * 0.5), 1);
    }

    private function trendOtifProveedor(
        float $otifDisplay,
        float $otifFactActual,
        float $otifFactAnterior,
        int $cantActual,
        int $cantAnterior
    ): float {
        if ($cantActual > 0 || $cantAnterior > 0) {
            $variacion = $this->variacionPctOtif($otifFactActual, $otifFactAnterior);
            if ($variacion != 0.0 || ($otifFactActual > 0 && $otifFactAnterior > 0)) {
                return $variacion;
            }
        }

        return round($otifDisplay - 60, 1);
    }

    private function trendMetricaProveedor(
        float $valorDisplay,
        float $actualFact,
        float $anteriorFact,
        int $cantActual,
        int $cantAnterior,
        float $meta = 80.0
    ): float {
        if ($cantActual > 0 || $cantAnterior > 0) {
            $variacion = $this->variacionPctOtif($actualFact, $anteriorFact);
            if ($variacion != 0.0 || ($actualFact > 0 && $anteriorFact > 0)) {
                return $variacion;
            }
        }

        return round($valorDisplay - $meta, 1);
    }

    private function pctOtifFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        return round($facturas->where('estatus', 'pagada')->count() / $total * 100, 1);
    }

    /**
     * Variación % de OTIF (misma lógica que Var. facturas/compras del reporte).
     */
    private function variacionPctOtif(float $actual, float $anterior): float
    {
        if ($anterior > 0) {
            return round((($actual - $anterior) / $anterior) * 100, 1);
        }

        return $actual > 0 ? 100.0 : 0.0;
    }

    /**
     * Tendencia OTIF en reporte anual: año vs año (facturas), trimestre (métricas) o vs meta 60%.
     */
    private function trendOtifReporte(
        ?ProveedorUser $prov,
        float $otifActual,
        float $otifAnterior,
        int $cantActual,
        int $cantAnterior,
        array $metricasProveedores,
        ?float $scoreFallback = null
    ): float {
        if ($cantActual > 0 || $cantAnterior > 0) {
            $variacion = $this->variacionPctOtif($otifActual, $otifAnterior);
            if ($variacion != 0.0 || ($otifActual > 0 && $otifAnterior > 0)) {
                return $variacion;
            }
        }

        if ($prov !== null) {
            $entrega = (float) $prov->score_entrega;
            $puntualidad = (float) $prov->score_puntualidad;
            $otif = $this->otifCompuesto($entrega, $puntualidad);

            return $this->trendOtifProveedor($otif, $otifActual, $otifAnterior, $cantActual, $cantAnterior);
        }

        $score = $scoreFallback ?? 0;

        return round($score - 60, 1);
    }

    /** In Full (IF): facturas pagadas / total (entrega completa facturada). */
    private function pctEntregaFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        return round($facturas->where('estatus', 'pagada')->count() / $total * 100, 1);
    }

    /** On Time (OT): facturas a tiempo según fecha de vencimiento. */
    private function pctPuntualidadFromFacturas($facturas): float
    {
        $total = $facturas->count();
        if ($total === 0) {
            return 0;
        }

        $aTiempo = $facturas->filter(fn ($f) => $this->facturaEsPuntual($f))->count();

        return round($aTiempo / $total * 100, 1);
    }

    private function facturaEsPuntual(Factura $f): bool
    {
        if ($f->estatus === 'cancelada') {
            return false;
        }

        if (! $f->fecha_vencimiento) {
            return $f->estatus === 'pagada';
        }

        $limite = $f->fecha_vencimiento->copy()->endOfDay();

        if ($f->estatus === 'pagada') {
            return $f->updated_at <= $limite;
        }

        return $f->estatus === 'pendiente' && now()->lte($limite);
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
