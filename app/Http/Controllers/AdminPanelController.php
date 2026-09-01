<?php

namespace App\Http\Controllers;

use App\Mail\OpinionPositivaAviso;
use App\Mail\SolicitudAltaAprobada;
use App\Models\AlertaConfiguracion;
use App\Models\Alerta;
use App\Models\ClienteUser;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\OcBorrador;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Models\SolicitudAlta;
use App\Models\SolicitudModificacionDatos;
use App\Services\AlertEngineService;
use App\Services\InventarioCalculoService;
use App\Services\PedidoProveedorSyncService;
use App\Services\ProveedorApiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AdminPanelController extends Controller
{
    // ── Dashboard general ──

    public function dashboard()
    {
        $this->asegurarPedidosConProveedor();

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

        $facturasPorEstatus = Factura::selectRaw('estatus, count(*) as cantidad, sum(total) as monto')
            ->whereNotNull('codigo_proveedor')
            ->groupBy('estatus')->get()->keyBy('estatus');

        $comprasPorMes = $this->ventasPorMesProveedores();
        $mesActualCompras = $comprasPorMes->last();
        $mesAnteriorCompras = $comprasPorMes->count() >= 2
            ? $comprasPorMes[$comprasPorMes->count() - 2]
            : null;
        $comprasVarPct = $this->calcularVariacionPct(
            (float) ($mesActualCompras['monto'] ?? 0),
            (float) ($mesAnteriorCompras['monto'] ?? 0)
        );
        $facturasProvVarPct = $this->calcularVariacionPct(
            (float) ($mesActualCompras['facturas'] ?? 0),
            (float) ($mesAnteriorCompras['facturas'] ?? 0)
        );

        $facturasPagadasCount = (int) data_get($facturasPorEstatus->get('pagada'), 'cantidad', 0);
        $facturasPendientesCount = (int) data_get($facturasPorEstatus->get('pendiente'), 'cantidad', 0);
        $facturasCanceladasCount = (int) data_get($facturasPorEstatus->get('cancelada'), 'cantidad', 0);

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

        $agotadosInicioMes = Producto::where('activo', true)
            ->where('stock', '<=', 0)
            ->where('updated_at', '<', $inicioMes)
            ->count();
        $agotadosVarPct = $this->calcularVariacionPct((float) $sinStock, (float) $agotadosInicioMes);

        $otifResumen = $this->calcularOtifResumen();
        $fiscalResumen = $this->calcularResumenFiscal();

        $data = [
            'totalProveedores' => ProveedorUser::count(),
            'proveedoresActivos' => ProveedorUser::where('activo', true)->count(),
            'scorePromedio' => round((float) ProveedorUser::avg('score_total'), 1),
            'totalPedidos' => Pedido::count(),
            'pedidosPendientes' => Pedido::whereIn('estatus', ['validacion', 'procesando'])->count(),
            'pedidosEntregados' => Pedido::where('estatus', 'entregado')->count(),
            'montoPedidos' => Pedido::sum('total'),
            'ventasTotales' => (float) Factura::whereNotNull('codigo_proveedor')->where('estatus', '!=', 'cancelada')->sum('total'),
            'ventasMesActual' => (float) ($mesActualCompras['monto'] ?? 0),
            'facturasProvMesActual' => (int) ($mesActualCompras['facturas'] ?? 0),
            'ventasVarPct' => $comprasVarPct,
            'facturasProvVarPct' => $facturasProvVarPct,
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
            'ultimosPedidos' => Pedido::with('proveedor')->orderBy('created_at', 'desc')->limit(3)->get(),
            'topProveedores' => ProveedorUser::where('score_total', '>', 0)->orderBy('score_total', 'desc')->limit(3)->get(),
            'proveedoresActivosList' => ProveedorUser::where('activo', true)->orderBy('nombre')->get(),
            'pedidosPorMes' => $pedidosPorMes,
            'facturasPorEstatus' => $facturasPorEstatus,
            'facturasPagadasCount' => $facturasPagadasCount,
            'facturasPendientesCount' => $facturasPendientesCount,
            'facturasCanceladasCount' => $facturasCanceladasCount,
            'otPercent' => $otifResumen['otPercent'],
            'ifPercent' => $otifResumen['ifPercent'],
            'fiscalVerde' => $fiscalResumen['verde'],
            'fiscalAmarillo' => $fiscalResumen['amarillo'],
            'fiscalRojo' => $fiscalResumen['rojo'],
            'fiscalGris' => $fiscalResumen['gris'],
            'fiscalPctCumple' => $fiscalResumen['pctCumple'],
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

    public function pedidosExcel(Request $request)
    {
        $query = Pedido::query();
        if ($request->input('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }
        if ($request->input('grupo') === 'pendientes') {
            $query->whereIn('estatus', ['validacion', 'procesando']);
        }
        if ($request->input('busqueda')) {
            $b = $request->input('busqueda');
            $query->where(function ($q) use ($b) {
                $q->where('folio', 'like', "%{$b}%")->orWhere('nombre_cliente', 'like', "%{$b}%");
            });
        }
        $pedidos = $query->orderBy('created_at', 'desc')->get();

        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['REPORTE DE PEDIDOS'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['FOLIO', 'PROVEEDOR', 'TOTAL', 'TIPO PAGO', 'ESTATUS', 'FECHA']];

        foreach ($pedidos as $p) {
            $lines[] = [$p->folio, $p->nombre_cliente, '$'.number_format((float) $p->total, 2), ucfirst($p->tipo_pago ?? '-'), ucfirst($p->estatus), $p->created_at?->format('d/m/Y')];
        }
        $lines[] = [];
        $lines[] = ['', 'TOTAL:', '$'.number_format((float) $pedidos->sum('total'), 2)];

        return $this->csvResponse($lines, 'Pedidos_'.now()->format('Y-m-d').'.csv');
    }

    public function pedidos(Request $request)
    {
        $this->asegurarPedidosConProveedor();

        $estatusOpciones = [
            'validacion' => 'En validación',
            'procesando' => 'En proceso',
            'enviado' => 'Enviado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
        ];

        $query = Pedido::with('proveedor');

        if ($busqueda = $request->input('busqueda')) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('folio', 'like', "%{$busqueda}%")
                    ->orWhere('nombre_proveedor', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_proveedor', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', function ($p) use ($busqueda) {
                        $p->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('id_proveedor', 'like', "%{$busqueda}%");
                    });
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

        $montoFiltrado = (float) (clone $query)->sum('total');
        $pedidos = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $conteosEstatus = Pedido::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');

        $totalGeneral = Pedido::count();
        $conteoPendientes = ($conteosEstatus['validacion'] ?? 0) + ($conteosEstatus['procesando'] ?? 0);
        $conteoEntregados = $conteosEstatus['entregado'] ?? 0;
        $pctEntregados = $totalGeneral > 0 ? round(($conteoEntregados / $totalGeneral) * 100, 1) : 0;

        $inicioMes = now()->startOfMonth();
        $inicioMesAnterior = now()->subMonth()->startOfMonth();
        $finMesAnterior = now()->subMonth()->endOfMonth();
        $pedidosMes = Pedido::where('created_at', '>=', $inicioMes)->count();
        $pedidosMesAnterior = Pedido::whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])->count();
        $montoMes = (float) Pedido::where('created_at', '>=', $inicioMes)->sum('total');
        $montoMesAnterior = (float) Pedido::whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])->sum('total');
        $trendPedidosMes = $this->calcularVariacionPct((float) $pedidosMes, (float) $pedidosMesAnterior) ?? 0;
        $trendMontoMes = $this->calcularVariacionPct($montoMes, $montoMesAnterior) ?? 0;

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
            'conteoEntregados',
            'pctEntregados',
            'totalGeneral',
            'montoFiltrado',
            'pedidosMes',
            'montoMes',
            'trendPedidosMes',
            'trendMontoMes',
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
            'rendimiento' => $request->input('rendimiento', ''),
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
            'rendimiento' => 'rendimiento',
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
                    ->orWhere('id_proveedor', 'like', "%{$b}%")
                    ->orWhere('correo', 'like', "%{$b}%")
                    ->orWhere('usuario', 'like', "%{$b}%");
                if ($id = $this->parseProveedorIdBusqueda($b)) {
                    $q->orWhere('id', $id);
                }
            });
        } else {
            if ($filtrosProv['nombre']) {
                $query->where('nombre', 'like', '%'.$filtrosProv['nombre'].'%');
            }
            if ($filtrosProv['codigo']) {
                $query->where('id_proveedor', 'like', '%'.$filtrosProv['codigo'].'%');
            }
            if ($filtrosProv['correo']) {
                $query->where('correo', 'like', '%'.$filtrosProv['correo'].'%');
            }
        }
        if ($filtrosProv['activo'] !== '') {
            $query->where('activo', $filtrosProv['activo'] === '1');
        }
        match ($filtrosProv['rendimiento']) {
            'alto' => $query->where('score_total', '>=', 80),
            'bajo' => $query->where('score_total', '<', 60),
            'facturas' => $query->whereIn('id_proveedor', Factura::where('estatus', 'pendiente')
                ->whereNotNull('codigo_proveedor')
                ->distinct()
                ->pluck('codigo_proveedor')),
            'ocs' => $query->whereIn('id', OcBorrador::where('estatus', '!=', 'completada')
                ->where('created_at', '<=', now()->subDays(30))
                ->whereNotNull('proveedor_id')
                ->distinct()
                ->pluck('proveedor_id')),
            default => null,
        };

        $proveedores = $query->orderBy('score_total', 'desc')->paginate(20)->withQueryString();
        $metricasProveedores = $this->buildProveedoresMetricas($proveedores->getCollection());

        // Primero solo IDs (evita Out of sort memory al ordenar filas con JSON `productos`).
        $ordenesQuery = OcBorrador::query()->orderByDesc('id');
        if ($filtrosOc['proveedor']) {
            $fp = $filtrosOc['proveedor'];
            $ordenesQuery->whereHas('proveedor', function ($pq) use ($fp) {
                $pq->where('nombre', 'like', '%'.$fp.'%')
                    ->orWhere('usuario', 'like', '%'.$fp.'%')
                    ->orWhere('id_proveedor', 'like', '%'.$fp.'%');
                if ($id = $this->parseProveedorIdBusqueda($fp)) {
                    $pq->orWhere('id', $id);
                }
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
        $ordenesIds = (clone $ordenesQuery)->limit(50)->pluck('id');
        $ordenes = $ordenesIds->isEmpty()
            ? collect()
            : OcBorrador::with('proveedor')
                ->whereIn('id', $ordenesIds)
                ->orderByDesc('id')
                ->get();

        $facturasQuery = Factura::where('estatus', 'pendiente')->whereNotNull('codigo_proveedor');
        if ($filtrosFact['folio']) {
            $facturasQuery->where('folio_cfdi', 'like', '%'.$filtrosFact['folio'].'%');
        }
        if ($filtrosFact['proveedor']) {
            $fp = $filtrosFact['proveedor'];
            $facturasQuery->where(function ($q) use ($fp) {
                $q->where('codigo_proveedor', 'like', '%'.$fp.'%');
                if ($id = $this->parseProveedorIdBusqueda($fp)) {
                    $q->orWhereHas('proveedor', fn ($pq) => $pq->where('id', $id));
                }
            });
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
        $facturasPendientes = $facturasQuery->with('proveedor')->orderBy('fecha_vencimiento')->limit(100)->get();

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

        $scorePromedio = round((float) ProveedorUser::where('activo', true)->avg('score_total'), 1);
        $montoFacturasPendientes = (float) Factura::where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->sum('total');
        $proveedoresAltoScore = ProveedorUser::where('activo', true)->where('score_total', '>=', 80)->count();
        $proveedoresBajoScore = ProveedorUser::where('activo', true)->where('score_total', '<', 60)->count();
        $proveedoresConFacturasPend = ProveedorUser::whereIn(
            'id_proveedor',
            Factura::where('estatus', 'pendiente')->whereNotNull('codigo_proveedor')->distinct()->pluck('codigo_proveedor')
        )->count();
        $proveedoresConOcVencidas = ProveedorUser::whereIn(
            'id',
            OcBorrador::where('estatus', '!=', 'completada')
                ->where('created_at', '<=', now()->subDays(30))
                ->whereNotNull('proveedor_id')
                ->distinct()
                ->pluck('proveedor_id')
        )->count();
        $comprasTrimTotal = (float) Factura::whereNotNull('codigo_proveedor')
            ->where('estatus', '!=', 'cancelada')
            ->where('created_at', '>=', now()->subMonths(3))
            ->sum('total');

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
            'scorePromedio',
            'montoFacturasPendientes',
            'proveedoresAltoScore',
            'proveedoresBajoScore',
            'proveedoresConFacturasPend',
            'proveedoresConOcVencidas',
            'comprasTrimTotal',
        ));
    }

    // ── Detalle facturas de un proveedor ──

    public function proveedorFacturas(string $codigo, ProveedorApiService $wieseApi)
    {
        $proveedor = ProveedorUser::whereCodigo($codigo)->first()
            ?? ProveedorUser::where('codigo', $codigo)->first();

        $facturas = Factura::where('codigo_proveedor', $codigo)->orderBy('fecha_vencimiento', 'desc')->get();

        // Código Wiese (campo codigo). Si vacío, se intenta con el de la URL.
        $wieseCodigo = trim((string) ($proveedor?->codigo ?? ''));
        if ($wieseCodigo === '') {
            $wieseCodigo = $codigo;
        }

        $fechaInicio = (string) request('fecha_inicio', now()->subYear()->startOfYear()->format('Y-m-d'));
        $fechaFin = (string) request('fecha_fin', now()->format('Y-m-d'));

        $ocItems = collect();
        $ocTotal = 0;
        $ocError = null;
        $ocLimit = 100;

        if ($wieseCodigo === '') {
            $ocError = 'Este proveedor no tiene código Wiese. Llénalo en el campo código.';
        } else {
            $ocResult = $wieseApi->listarDocumentosOCPorProveedorFechas($wieseCodigo, $fechaInicio, $fechaFin);
            if ($ocResult['success'] ?? false) {
                $all = collect($ocResult['data']['items'] ?? []);
                $ocTotal = (int) ($ocResult['data']['total'] ?? $all->count());
                $ocItems = $all->take($ocLimit);
            } else {
                $ocError = $ocResult['message'] ?? 'No se pudieron cargar las OC desde Wiese.';
            }
        }

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
                    '$'.number_format((float) $f->total, 2),
                    $f->fecha_vencimiento?->format('d/m/Y') ?? '-',
                    $vencida ? $diasV.' dias' : 'Vigente',
                    ucfirst($f->estatus),
                ];
            }
            $lines[] = [];
            $lines[] = ['', '', 'TOTAL DEUDA:', '$'.number_format((float) $facturas->where('estatus', 'pendiente')->sum('total'), 2)];

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

        return view('admin.proveedor-facturas', compact(
            'proveedor',
            'facturas',
            'codigo',
            'wieseCodigo',
            'fechaInicio',
            'fechaFin',
            'ocItems',
            'ocTotal',
            'ocError',
            'ocLimit'
        ));
    }

    // ── Exportar facturas pendientes a Excel ──

    public function facturasPendientesExcel()
    {
        $facturas = Factura::where('estatus', 'pendiente')
            ->whereNotNull('codigo_proveedor')
            ->orderBy('codigo_proveedor')
            ->get();

        $productos = Producto::where('activo', true)->get();
        $filename = 'Facturas_Pendientes_Proveedores_'.now()->format('Y-m-d').'.csv';

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['FACTURAS PENDIENTES DE PROVEEDORES CON PRODUCTOS'];
        $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
        $lines[] = [];

        foreach ($facturas as $f) {
            $prov = ProveedorUser::where('id_proveedor', $f->codigo_proveedor)->first();
            $vencida = $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
            $diasV = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;

            $lines[] = ['PROVEEDOR:', $prov->nombre ?? $f->codigo_proveedor, 'CODIGO:', $f->codigo_proveedor, 'TOTAL ADEUDO:', '$'.number_format((float) $f->total, 2), 'VENCIMIENTO:', $f->fecha_vencimiento?->format('d/m/Y') ?? '-', 'DIAS VENCIDO:', $vencida ? $diasV.' dias' : 'Vigente'];
            $lines[] = ['COD. PRODUCTO', 'NOMBRE PRODUCTO', 'PRECIO', 'STOCK', 'UNIDAD'];

            foreach ($productos as $prod) {
                $lines[] = [
                    $prod->codigo,
                    $prod->nombre,
                    '$'.number_format((float) $prod->precio, 2),
                    number_format((int) $prod->stock),
                    $prod->unidad_venta,
                ];
            }
            $lines[] = [];
        }

        $lines[] = ['TOTAL DEUDA GENERAL:', '', '$'.number_format((float) $facturas->sum('total'), 2)];

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

    private function asegurarPedidosConProveedor(): void
    {
        $sync = app(PedidoProveedorSyncService::class);
        if (! $sync->columnasDisponibles()) {
            return;
        }

        $pendientes = Pedido::whereNull('codigo_proveedor')
            ->orWhereNull('nombre_proveedor')
            ->exists();

        if ($pendientes) {
            $sync->sincronizar();
        }
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

    public function productosExcel(Request $request)
    {
        $productos = $this->queryProductosFiltrados($request)
            ->orderBy('created_at', 'desc')
            ->get();

        $lines = [
            ['INDUSTRIAS SALCOM S.A. DE C.V.'],
            ['CATÁLOGO DE PRODUCTOS'],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['CÓDIGO', 'NOMBRE', 'TIPO PRODUCTO', 'UNIDAD', 'PROVEEDOR', 'FECHA ALTA'],
        ];

        foreach ($productos as $p) {
            $fecha = '—';
            if ($p->created_at) {
                try {
                    $fecha = Carbon::parse($p->created_at)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    $fecha = '—';
                }
            }
            $lines[] = [
                $p->codigo,
                $p->nombre,
                $p->tipo_producto ?: ($p->categoria ?: '—'),
                $p->unidad_venta ?: '—',
                $p->proveedor_nombre ?: '—',
                $fecha,
            ];
        }

        return $this->csvResponse($lines, 'Productos_'.now()->format('Y-m-d').'.csv');
    }

    /**
     * Actualizar producto (categoría, precio, unidad, stock, descripción) via AJAX.
     */
    public function actualizarProducto(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $campos = $request->only(['categoria', 'precio', 'unidad_venta', 'stock', 'descripcion']);

        if (isset($campos['precio'])) {
            $campos['precio'] = (float) str_replace(['$', ','], '', $campos['precio']);
        }
        if (isset($campos['stock'])) {
            $campos['stock'] = (int) $campos['stock'];
        }

        $producto->update($campos);

        return response()->json(['success' => true, 'mensaje' => 'Producto actualizado']);
    }

    /**
     * Borrar producto via AJAX.
     */
    public function borrarProducto($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return response()->json(['success' => true, 'mensaje' => 'Producto eliminado']);
    }

    /**
     * Activar/inactivar producto via AJAX (toggle del campo `activo`).
     */
    public function toggleActivoProducto($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->activo = ! $producto->activo;
        $producto->save();

        return response()->json([
            'success' => true,
            'activo' => (bool) $producto->activo,
            'mensaje' => $producto->activo ? 'Producto activado' : 'Producto marcado como inactivo',
        ]);
    }

    /**
     * Vista detalle de un producto con todas sus especificaciones.
     */
    public function productoDetalle($id)
    {
        $producto = Producto::findOrFail($id);

        return view('admin.producto-detalle', compact('producto'));
    }

    public function productos(Request $request)
    {
        $stockOpciones = $this->stockOpcionesProductos();

        $busqueda = $request->input('busqueda', '');
        $stock = $request->input('stock', '');
        $grupo = $request->input('grupo', '');
        $activo = $request->input('activo', '');
        $categoria = $request->input('categoria', '');
        $proveedor = $request->input('proveedor', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        if ($request->input('sin_stock')) {
            $stock = 'agotado';
        }

        $productos = $this->queryProductosFiltrados($request)
            ->with(['preciosProveedor.proveedor'])
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $totalGeneral = Producto::count();
        $conteoAgotado = Producto::where('stock', '<=', 0)->count();
        $conteoBajo = Producto::where('stock', '>', 0)->where('stock', '<', 50)->count();
        $conteoOk = Producto::where('stock', '>=', 50)->count();
        $conteoCriticos = $conteoAgotado + $conteoBajo;
        $conteoInactivos = Producto::where('activo', false)->count();
        $conteoActivos = Producto::where('activo', true)->count();
        $saludPct = $totalGeneral > 0 ? round(($conteoOk / $totalGeneral) * 100, 1) : 0;
        $valorInventario = (float) Producto::where('activo', true)
            ->get()
            ->sum(fn ($p) => (float) $p->stock * (float) $p->precio);
        $categorias = Producto::whereNotNull('tipo_producto')
            ->where('tipo_producto', '!=', '')
            ->distinct()
            ->orderBy('tipo_producto')
            ->pluck('tipo_producto');
        $totalCategorias = $categorias->count();

        $proveedores = Producto::whereNotNull('proveedor_nombre')
            ->where('proveedor_nombre', '!=', '')
            ->distinct()
            ->orderBy('proveedor_nombre')
            ->pluck('proveedor_nombre');

        $admins = Producto::whereNotNull('proveedor_nombre')
            ->where('proveedor_nombre', '!=', '')
            ->where('proveedor_tipo', 'admin')
            ->distinct()
            ->orderBy('proveedor_nombre')
            ->pluck('proveedor_nombre');

        $filtros = [
            'busqueda' => $busqueda,
            'stock' => $stock,
            'grupo' => $grupo,
            'activo' => $activo,
            'categoria' => $categoria,
            'proveedor' => $proveedor,
            'admin' => $request->input('admin', ''),
            'unidad' => $request->input('unidad', ''),
            'codigo' => $request->input('codigo', ''),
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
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
            'conteoActivos',
            'saludPct',
            'valorInventario',
            'totalCategorias',
            'categorias',
            'proveedores',
            'admins',
            'filtros',
            'filtrosActivos',
        ));
    }

    private function stockOpcionesProductos(): array
    {
        return [
            'agotado' => 'Agotado',
            'bajo' => 'Stock bajo',
            'ok' => 'Stock OK',
        ];
    }

    private function queryProductosFiltrados(Request $request)
    {
        $stockOpciones = $this->stockOpcionesProductos();
        $query = Producto::query();

        $busqueda = $request->input('busqueda', '');
        $stock = $request->input('stock', '');
        $grupo = $request->input('grupo', '');
        $activo = $request->input('activo', '');
        $categoria = $request->input('categoria', '');
        $proveedor = $request->input('proveedor', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('codigo', 'like', "%{$busqueda}%")
                    ->orWhere('codigo_alterno', 'like', "%{$busqueda}%")
                    ->orWhere('categoria', 'like', "%{$busqueda}%")
                    ->orWhere('proveedor_nombre', 'like', "%{$busqueda}%");
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
            $query->where('tipo_producto', $categoria);
        }

        if ($proveedor) {
            $query->where('proveedor_nombre', $proveedor)->where('proveedor_tipo', 'proveedor');
        }

        $admin = $request->input('admin', '');
        if ($admin) {
            $query->where('proveedor_nombre', $admin)->where('proveedor_tipo', 'admin');
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $unidad = $request->input('unidad', '');
        if ($unidad) {
            $query->where('unidad_venta', $unidad);
        }

        return $query;
    }

    // ── Facturas ──

    public function facturas(Request $request)
    {
        $estatusOpciones = $this->estatusOpcionesFacturas();

        $busqueda = $request->input('busqueda', '');
        $estatus = $request->input('estatus', '');
        $vencidas = $request->input('vencidas', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        $query = $this->queryFacturasFiltradas($request);
        $montoFiltrado = (float) (clone $query)->sum('total');
        $facturas = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        $baseProveedor = Factura::whereNotNull('codigo_proveedor');
        $totalGeneral = (clone $baseProveedor)->count();
        $conteosEstatus = (clone $baseProveedor)->selectRaw('estatus, count(*) as cantidad')
            ->groupBy('estatus')
            ->pluck('cantidad', 'estatus');
        $conteoVencidas = (clone $baseProveedor)
            ->where('estatus', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->count();
        $conteoPendientes = $conteosEstatus['pendiente'] ?? 0;
        $conteoPagadas = $conteosEstatus['pagada'] ?? 0;
        $montoPendiente = (float) (clone $baseProveedor)->where('estatus', 'pendiente')->sum('total');
        $montoPagado = (float) (clone $baseProveedor)->where('estatus', 'pagada')->sum('total');
        $montoVencidas = (float) (clone $baseProveedor)
            ->where('estatus', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->sum('total');
        $pctPagadas = $totalGeneral > 0 ? round(($conteoPagadas / $totalGeneral) * 100, 1) : 0;

        $filtros = [
            'busqueda' => $busqueda,
            'estatus' => $estatus,
            'vencidas' => $vencidas,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];

        $filtrosActivos = $this->filtrosTienenValor($filtros);

        $codigosPagina = $facturas->getCollection()
            ->pluck('codigo_proveedor')
            ->filter()
            ->unique()
            ->values();

        $saldosPendientesProveedor = $codigosPagina->isEmpty()
            ? collect()
            : Factura::query()
                ->whereIn('codigo_proveedor', $codigosPagina)
                ->whereIn('estatus', ['pendiente', 'programada'])
                ->groupBy('codigo_proveedor')
                ->selectRaw('codigo_proveedor, COALESCE(SUM(total), 0) as saldo')
                ->pluck('saldo', 'codigo_proveedor');

        // Anticipos aplicados por factura (para la columna "Anticipos").
        $idsPagina = $facturas->getCollection()->pluck('id')->filter()->unique()->values();
        $anticiposPorFactura = $idsPagina->isEmpty()
            ? collect()
            : \App\Models\AnticipoProveedor::query()
                ->whereIn('factura_id', $idsPagina)
                ->where('estatus', 'aplicado')
                ->get(['id', 'factura_id', 'folio_general', 'monto_aplicado', 'total_banco', 'fecha'])
                ->groupBy('factura_id');

        return view('admin.facturas', compact(
            'facturas',
            'estatus',
            'vencidas',
            'estatusOpciones',
            'totalGeneral',
            'conteosEstatus',
            'conteoVencidas',
            'conteoPendientes',
            'conteoPagadas',
            'montoPendiente',
            'montoPagado',
            'montoVencidas',
            'montoFiltrado',
            'pctPagadas',
            'filtros',
            'filtrosActivos',
            'saldosPendientesProveedor',
            'anticiposPorFactura',
        ));
    }

    private function estatusOpcionesFacturas(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'programada' => 'Programada',
            'pagada' => 'Pagada',
            'liquidada' => 'Liquidada',
            'cancelada' => 'Cancelada',
            'rechazada' => 'Rechazada',
        ];
    }

    private function queryFacturasFiltradas(Request $request)
    {
        $estatusOpciones = $this->estatusOpcionesFacturas();
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
                            ->orWhere('id_proveedor', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($vencidas) {
            $query->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        } elseif ($estatus === 'programada') {
            $query->whereIn('estatus', ['programada', 'aprobada', 'validada']);
        } elseif ($estatus && array_key_exists($estatus, $estatusOpciones)) {
            $query->where('estatus', $estatus);
        }

        if ($fechaDesde) {
            $query->whereDate('fecha_vencimiento', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha_vencimiento', '<=', $fechaHasta);
        }

        return $query;
    }

    // ── Documentos de proveedores ──

    public function documentosExcel(Request $request)
    {
        $estatusOpciones = $this->estatusOpcionesDocumentos();
        $tipoLabels = $this->tipoLabelsDocumentos();
        $documentos = $this->queryDocumentosFiltrados($request)
            ->orderByRaw("CASE estatus WHEN 'pendiente' THEN 1 WHEN 'rechazado' THEN 2 WHEN 'aprobado' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $lines = [
            ['INDUSTRIAS SALCOM S.A. DE C.V.'],
            ['DOCUMENTOS FISCALES DE PROVEEDORES'],
            ['Generado: '.now()->format('d/m/Y H:i')],
            [],
            ['PROVEEDOR', 'ID SISTEMA', 'ID PROVEEDOR', 'TIPO', 'ESTATUS', 'NOTAS', 'FECHA REVISIÓN', 'SUBIDO'],
        ];

        foreach ($documentos as $d) {
            $prov = $d->proveedor;
            $lines[] = [
                ($prov !== null ? ($prov->nombre ?? $prov->usuario) : null) ?? 'ID: '.$d->proveedor_id,
                $d->proveedor_id,
                ($prov !== null ? $prov->id_proveedor : null) ?? '—',
                $tipoLabels[$d->tipo] ?? $d->tipo,
                $estatusOpciones[$d->estatus] ?? ucfirst($d->estatus),
                $d->notas_revision ?? '—',
                $d->revisado_at?->format('d/m/Y') ?? '—',
                $d->created_at?->format('d/m/Y') ?? '—',
            ];
        }

        return $this->csvResponse($lines, 'Documentos_Proveedores_'.now()->format('Y-m-d').'.csv');
    }

    public function documentos(Request $request)
    {
        $estatusOpciones = $this->estatusOpcionesDocumentos();
        $tipoLabels = $this->tipoLabelsDocumentos();

        $busqueda = $request->input('busqueda', '');
        $estatus = $request->input('estatus', '');
        $tipo = $request->input('tipo', '');
        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');

        $documentos = $this->queryDocumentosFiltrados($request)
            ->orderByRaw("CASE estatus WHEN 'pendiente' THEN 1 WHEN 'rechazado' THEN 2 WHEN 'aprobado' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $totalGeneral = DocumentoProveedor::count();
        $conteosEstatus = DocumentoProveedor::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');
        $conteoPendientes = $conteosEstatus['pendiente'] ?? 0;
        $conteoAprobados = $conteosEstatus['aprobado'] ?? 0;
        $conteoRechazados = $conteosEstatus['rechazado'] ?? 0;
        $pctAprobados = $totalGeneral > 0 ? round(($conteoAprobados / $totalGeneral) * 100, 1) : 0;
        $proveedoresConPendientes = DocumentoProveedor::where('estatus', 'pendiente')->distinct('proveedor_id')->count('proveedor_id');

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
            'conteoPendientes',
            'conteoAprobados',
            'conteoRechazados',
            'pctAprobados',
            'proveedoresConPendientes',
            'filtros',
            'filtrosActivos',
        ));
    }

    private function estatusOpcionesDocumentos(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
        ];
    }

    private function tipoLabelsDocumentos(): array
    {
        return [
            'cif' => 'CIF',
            'opinion' => 'Opinión positiva',
            'caratula_banco' => 'Carátula banco',
            'acta_constitutiva' => 'Acta constitutiva',
            'comprobante_domicilio' => 'Comprobante domicilio',
        ];
    }

    private function queryDocumentosFiltrados(Request $request)
    {
        $estatusOpciones = $this->estatusOpcionesDocumentos();
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
                            ->orWhere('id_proveedor', 'like', "%{$busqueda}%")
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

        return $query;
    }

    // ── Negocio ──

    public function negocio()
    {
        $baseFacturas = Factura::whereNotNull('codigo_proveedor');
        $ventasPorMes = $this->ventasPorMesProveedores();

        $data = [
            'ventasTotales' => (float) (clone $baseFacturas)->where('estatus', '!=', 'cancelada')->sum('total'),
            'ventasCount' => (int) (clone $baseFacturas)->where('estatus', '!=', 'cancelada')->count(),
            'deudasTotal' => (float) (clone $baseFacturas)->where('estatus', 'pendiente')->sum('total'),
            'deudasCount' => (int) (clone $baseFacturas)->where('estatus', 'pendiente')->count(),
            'cobradoTotal' => (float) (clone $baseFacturas)->where('estatus', 'pagada')->sum('total'),
            'cobradoCount' => (int) (clone $baseFacturas)->where('estatus', 'pagada')->count(),
            'proveedoresVentas' => $this->resumenNegocioProveedores('ventas'),
            'proveedoresDeudas' => $this->resumenNegocioProveedores('deudas'),
            'proveedoresCobrado' => $this->resumenNegocioProveedores('cobrado'),
            'ventasPorMes' => $ventasPorMes,
            'chartTotal6m' => (float) $ventasPorMes->sum('monto'),
            'chartPromedio6m' => round($ventasPorMes->avg('monto') ?: 0),
            'chartMesPico' => $ventasPorMes->sortByDesc('monto')->first(),
        ];

        return view('admin.negocio', $data);
    }

    // ── OTIF ──

    public function otif()
    {
        $facturasProveedor = Factura::whereNotNull('codigo_proveedor')->get();
        $total = $facturasProveedor->count();
        $pagadas = $facturasProveedor->where('estatus', 'pagada')->count();
        $pendientes = $facturasProveedor->where('estatus', 'pendiente')->count();
        $vencidas = $facturasProveedor->where('estatus', 'pendiente')
            ->filter(fn ($f) => $f->fecha_vencimiento && $f->fecha_vencimiento->isPast())->count();
        $canceladas = $facturasProveedor->where('estatus', 'cancelada')->count();

        $otPercent = $this->pctPuntualidadFromFacturas($facturasProveedor);
        $ifPercent = $this->pctEntregaFromFacturas($facturasProveedor);
        $scoreGeneral = round(($otPercent + $ifPercent) / 2, 1);

        $trimInicio = now()->subMonths(3);
        $trimAnterior = now()->subMonths(6);
        $facturasTrim = $facturasProveedor->filter(fn ($f) => $f->created_at >= $trimInicio);
        $facturasTrimAnterior = $facturasProveedor->filter(
            fn ($f) => $f->created_at >= $trimAnterior && $f->created_at < $trimInicio
        );
        $trendOt = $this->deltaTrend(
            $this->pctPuntualidadFromFacturas($facturasTrim),
            $this->pctPuntualidadFromFacturas($facturasTrimAnterior)
        );
        $trendIf = $this->deltaTrend(
            $this->pctEntregaFromFacturas($facturasTrim),
            $this->pctEntregaFromFacturas($facturasTrimAnterior)
        );

        $proveedores = ProveedorUser::where('activo', true)->orderBy('score_total', 'desc')->get();
        $metricasProveedores = $this->buildProveedoresMetricas($proveedores);

        $detalleProveedores = [];
        foreach ($proveedores as $prov) {
            if (! $prov->id_proveedor) {
                continue;
            }

            $factProv = $facturasProveedor->where('codigo_proveedor', $prov->id_proveedor);
            $totalProv = $factProv->count();
            if ($totalProv === 0) {
                continue;
            }

            $m = $metricasProveedores[$prov->id] ?? [];
            $otProv = $this->pctPuntualidadFromFacturas($factProv);
            $ifProv = $this->pctEntregaFromFacturas($factProv);

            $detalleProveedores[] = [
                'nombre' => $prov->nombre ?? $prov->usuario,
                'codigo' => $prov->id_proveedor,
                'total' => $totalProv,
                'pagadas' => $factProv->where('estatus', 'pagada')->count(),
                'pendientes' => $factProv->where('estatus', 'pendiente')->count(),
                'ot' => $otProv,
                'if' => $ifProv,
                'score' => (float) $prov->score_total,
                'trend_ot' => $m['trend_puntualidad'] ?? 0,
                'trend_if' => $m['trend_entrega'] ?? 0,
                'trend_score' => $m['trend_otif'] ?? 0,
                'score_class' => $m['score_class'] ?? $this->scoreBarClass((float) $prov->score_total),
            ];
        }

        usort($detalleProveedores, fn ($a, $b) => $b['score'] <=> $a['score']);

        $facturasVencidas = Factura::whereNotNull('codigo_proveedor')
            ->where('estatus', 'pendiente')
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now())
            ->with('proveedor')
            ->orderBy('fecha_vencimiento')
            ->limit(25)
            ->get();

        $proveedoresConFacturas = count($detalleProveedores);
        $proveedoresActivos = $proveedores->count();

        return view('admin.otif', compact(
            'total', 'pagadas', 'pendientes', 'vencidas', 'canceladas',
            'otPercent', 'ifPercent', 'scoreGeneral', 'trendOt', 'trendIf',
            'detalleProveedores', 'facturasVencidas', 'facturasProveedor',
            'proveedoresConFacturas', 'proveedoresActivos'
        ));
    }

    // ── Inventario ──

    public function inventario(Request $request)
    {
        $filtro = $request->input('stock', 'all');
        $baseQuery = Producto::where('activo', true);

        $totalProductos = (clone $baseQuery)->count();
        $totalStock = (clone $baseQuery)->sum('stock');
        $sinStock = (clone $baseQuery)->where('stock', '<=', 0)->count();
        $stockBajo = (clone $baseQuery)->where('stock', '>', 0)->where('stock', '<', 50)->count();
        $stockOk = (clone $baseQuery)->where('stock', '>=', 50)->count();

        $query = Producto::where('activo', true);
        match ($filtro) {
            'out' => $query->where('stock', '<=', 0),
            'low' => $query->where('stock', '>', 0)->where('stock', '<', 50),
            'ok' => $query->where('stock', '>=', 50),
            default => null,
        };

        $productos = $query->orderBy('stock', 'asc')->paginate(10)->withQueryString();

        $proveedoresUsers = ProveedorUser::select('nombre', 'telefono', 'correo')->get();
        $proveedoresContacto = [];
        foreach ($productos as $producto) {
            $nombre = $producto->proveedor_nombre;
            if (! $nombre || isset($proveedoresContacto[$nombre])) {
                continue;
            }
            $proveedoresContacto[$nombre] = $proveedoresUsers->first(
                fn ($u) => str_contains(mb_strtolower($u->nombre), mb_strtolower($nombre))
            );
        }

        return view('admin.inventario', compact(
            'productos',
            'totalStock',
            'sinStock',
            'stockBajo',
            'stockOk',
            'totalProductos',
            'filtro',
            'proveedoresContacto'
        ));
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

    private function ventasPorMesProveedores()
    {
        $mesesNombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $data = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $base = Factura::whereNotNull('codigo_proveedor')
                ->where('estatus', '!=', 'cancelada')
                ->whereYear('created_at', $fecha->year)
                ->whereMonth('created_at', $fecha->month);
            $montoPagado = (float) (clone $base)->where('estatus', 'pagada')->sum('total');
            $montoPendiente = (float) (clone $base)->where('estatus', 'pendiente')->sum('total');
            $data->push([
                'mes' => $mesesNombres[(int) $fecha->format('n')].' '.$fecha->format('Y'),
                'mes_corto' => $mesesNombres[(int) $fecha->format('n')],
                'facturas' => (clone $base)->count(),
                'monto' => $montoPagado + $montoPendiente,
                'monto_pagado' => $montoPagado,
                'monto_pendiente' => $montoPendiente,
            ]);
        }

        return $data;
    }

    private function resumenNegocioProveedores(string $tipo): array
    {
        $query = Factura::whereNotNull('codigo_proveedor');

        if ($tipo === 'ventas' || $tipo === 'compras') {
            $query->where('estatus', '!=', 'cancelada');
        } elseif ($tipo === 'deudas') {
            $query->where('estatus', 'pendiente');
        } elseif ($tipo === 'cobrado') {
            $query->where('estatus', 'pagada');
        }

        $agrupado = $query
            ->selectRaw('codigo_proveedor, count(*) as num_facturas, sum(total) as monto_total, max(created_at) as ultima_factura')
            ->groupBy('codigo_proveedor')
            ->orderByDesc('monto_total')
            ->get();

        $codigos = $agrupado->pluck('codigo_proveedor')->filter()->values();
        $proveedores = ProveedorUser::whereIn('id_proveedor', $codigos)->get()->keyBy('id_proveedor');

        // Obtener categoría principal de productos por proveedor
        $categoriasProv = Producto::whereIn('proveedor_nombre', $proveedores->pluck('nombre')->filter())
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->selectRaw('proveedor_nombre, categoria, count(*) as total')
            ->groupBy('proveedor_nombre', 'categoria')
            ->orderByDesc('total')
            ->get()
            ->groupBy('proveedor_nombre')
            ->map(fn ($g) => $g->first()->categoria);

        return $agrupado->map(function ($row) use ($proveedores, $categoriasProv) {
            $prov = $proveedores->get($row->codigo_proveedor);
            $nombreProv = $prov->nombre ?? $prov->usuario ?? $row->codigo_proveedor;

            return [
                'codigo' => $row->codigo_proveedor,
                'nombre' => $nombreProv,
                'correo' => $prov->correo ?? null,
                'telefono' => $prov->telefono ?? null,
                'facturas' => (int) data_get($row, 'num_facturas', 0),
                'monto' => (float) data_get($row, 'monto_total', 0),
                'score' => (float) ($prov->score_total ?? 0),
                'categoria' => $categoriasProv->get($nombreProv, '—'),
                'ultima_hora' => data_get($row, 'ultima_factura') ? Carbon::parse(data_get($row, 'ultima_factura'))->format('h:i a') : '—',
            ];
        })->values()->all();
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
            $facturasAnterior = Factura::where('codigo_proveedor', $prov->id_proveedor)
                ->whereYear('created_at', $anioAnterior)->get();
            $facturasActual = Factura::where('codigo_proveedor', $prov->id_proveedor)
                ->whereYear('created_at', $anioActual)->get();

            $montoAnterior = $facturasAnterior->sum('total');
            $montoActual = $facturasActual->sum('total');
            $cantAnterior = $facturasAnterior->count();
            $cantActual = $facturasActual->count();

            $variacionMonto = $montoAnterior > 0 ? round((($montoActual - $montoAnterior) / $montoAnterior) * 100, 1) : ($montoActual > 0 ? 100 : 0);
            $variacionCant = $cantAnterior > 0 ? round((($cantActual - $cantAnterior) / $cantAnterior) * 100, 1) : ($cantActual > 0 ? 100 : 0);

            $reporte[] = [
                'id' => $prov->id,
                'codigo' => $prov->id_proveedor,
                'nombre' => $prov->nombre ?? $prov->usuario,
                'compras_anterior' => $montoAnterior,
                'compras_actual' => $montoActual,
                'facturas_anterior' => $cantAnterior,
                'facturas_actual' => $cantActual,
                'variacion_monto' => $variacionMonto,
                'variacion_cant' => $variacionCant,
                'score' => $prov->score_total,
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

        return view('admin.reporte-proveedores', compact('reporte', 'totales', 'anioActual', 'anioAnterior'));
    }

    public function reporteProveedoresExcel()
    {
        $anioActual = (int) date('Y');
        $anioAnterior = $anioActual - 1;

        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();

        $filename = 'Reporte_Proveedores_'.$anioActual.'.csv';

        // Generar CSV en memoria
        $output = chr(0xEF).chr(0xBB).chr(0xBF); // BOM UTF-8

        $lines = [];
        $lines[] = ['INDUSTRIAS SALCOM S.A. DE C.V.'];
        $lines[] = ['REPORTE DE COMPRAS POR PROVEEDOR - COMPARATIVO ANUAL'];
        $lines[] = ['Generado: '.now()->format('d/m/Y H:i')];
        $lines[] = [];
        $lines[] = ['ID SISTEMA', 'ID PROVEEDOR', 'PROVEEDOR', 'SCORE', "FACTURAS {$anioAnterior}", "FACTURAS {$anioActual}", 'VAR FACTURAS %', "COMPRAS {$anioAnterior}", "COMPRAS {$anioActual}", 'VAR COMPRAS %'];

        $totalAnterior = 0;
        $totalActual = 0;

        foreach ($proveedores as $prov) {
            $facturasAnt = Factura::where('codigo_proveedor', $prov->id_proveedor)->whereYear('created_at', $anioAnterior)->get();
            $facturasAct = Factura::where('codigo_proveedor', $prov->id_proveedor)->whereYear('created_at', $anioActual)->get();

            $montoAnt = $facturasAnt->sum('total');
            $montoAct = $facturasAct->sum('total');
            $cantAnt = $facturasAnt->count();
            $cantAct = $facturasAct->count();

            $varMonto = $montoAnt > 0 ? round((($montoAct - $montoAnt) / $montoAnt) * 100, 1) : 0;
            $varCant = $cantAnt > 0 ? round((($cantAct - $cantAnt) / $cantAnt) * 100, 1) : 0;

            $lines[] = [
                $prov->id,
                $prov->id_proveedor ?? '—',
                $prov->nombre ?? $prov->usuario,
                $prov->score_total.'%',
                $cantAnt,
                $cantAct,
                $varCant.'%',
                '$'.number_format($montoAnt, 2),
                '$'.number_format($montoAct, 2),
                $varMonto.'%',
            ];

            $totalAnterior += $montoAnt;
            $totalActual += $montoAct;
        }

        $lines[] = [];
        $varTotal = $totalAnterior > 0 ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 1) : 0;
        $lines[] = ['', 'GRAN TOTAL', '', '', '', '', '$'.number_format($totalAnterior, 2), '$'.number_format($totalActual, 2), $varTotal.'%'];

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
                'id' => $prov->id,
                'codigo' => $prov->id_proveedor,
                'nombre' => $prov->nombre ?? $prov->usuario,
                'meses' => [],
                'total' => 0,
            ];

            for ($m = 1; $m <= $mesActual; $m++) {
                $monto = Factura::where('codigo_proveedor', $prov->id_proveedor)
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
        $header = ['ID SISTEMA', 'ID PROVEEDOR', 'PROVEEDOR'];
        for ($m = 1; $m <= $mesActual; $m++) {
            $header[] = strtoupper(substr($this->mesNombre($m), 0, 3));
        }
        $header[] = 'TOTAL ACUMULADO';
        $lines[] = $header;

        $totalesMes = array_fill(1, $mesActual, 0);
        $granTotal = 0;

        foreach ($proveedores as $prov) {
            $row = [$prov->id, $prov->id_proveedor ?? '—', $prov->nombre ?? $prov->usuario];
            $totalProv = 0;

            for ($m = 1; $m <= $mesActual; $m++) {
                $monto = Factura::where('codigo_proveedor', $prov->id_proveedor)
                    ->whereYear('created_at', $anio)
                    ->whereMonth('created_at', $m)
                    ->sum('total');
                $row[] = '$'.number_format($monto, 2);
                $totalProv += $monto;
                $totalesMes[$m] += $monto;
            }

            $row[] = '$'.number_format($totalProv, 2);
            $granTotal += $totalProv;
            $lines[] = $row;
        }

        // Totales
        $lines[] = [];
        $totalRow = ['', 'TOTAL POR MES'];
        for ($m = 1; $m <= $mesActual; $m++) {
            $totalRow[] = '$'.number_format($totalesMes[$m], 2);
        }
        $totalRow[] = '$'.number_format($granTotal, 2);
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

        $inventarioService = app(InventarioCalculoService::class);
        $diasPedido = (int) AlertaConfiguracion::get('dias_alerta_documento', 7);
        try {
            $reporteInventario = collect($inventarioService->generarReporteCompleto())->keyBy('codigo');
        } catch (\Throwable $e) {
            report($e);
            $reporteInventario = collect();
        }
        $inventarioDias = [];
        foreach ($productos as $prod) {
            $row = $reporteInventario->get($prod->codigo);
            $inventarioDias[] = [
                'producto' => $prod,
                'dias_inventario' => $row['dias_inventario'] ?? 0,
                'dias_pedido' => $diasPedido,
                'dias_entrega' => $row['dias_entrega'] ?? 15,
            ];
        }

        // OC por proveedor — órdenes pendientes y atrasadas (limit + order by id: evita OOM sort)
        $ocPendientesIds = OcBorrador::whereIn('estatus', ['pendiente', 'aprobada'])
            ->orderByDesc('id')
            ->limit(100)
            ->pluck('id');
        $ocProveedores = OcBorrador::with('proveedor')
            ->whereIn('id', $ocPendientesIds)
            ->orderByDesc('id')
            ->get()
            ->map(function ($oc) {
                $fechaOC = $oc->created_at;
                $fechaVencimiento = $oc->aprobada_at ? $oc->aprobada_at->addDays(15) : $fechaOC->addDays(15);
                $diasAtraso = now()->gt($fechaVencimiento) ? now()->diffInDays($fechaVencimiento) : 0;
                $productosOC = is_array($oc->productos) ? $oc->productos : [];

                return [
                    'oc' => $oc,
                    'proveedor' => $oc->proveedor,
                    'fecha_oc' => $fechaOC,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'dias_atraso' => $diasAtraso,
                    'productos' => $productosOC,
                    'atrasada' => $diasAtraso > 0,
                ];
            });

        $conteoOpinionPendiente = collect($opinionData)->whereIn('estatus', ['pendiente', 'sin_documento'])->count();
        $conteoOpinionOk = collect($opinionData)->where('estatus', 'aprobado')->count();
        $totalProveedores = ProveedorUser::count();
        $conteoProveedoresActivos = ProveedorUser::where('activo', true)->count();
        $conteoInventarioCritico = collect($inventarioDias)
            ->filter(fn ($i) => ($i['dias_inventario'] < ($i['dias_pedido'] + $i['dias_entrega'])))
            ->count();
        $conteoOcAtrasadas = $ocProveedores->where('atrasada', true)->count();

        return view('admin.gestion-compras', compact(
            'proveedores',
            'productos',
            'opinionData',
            'inventarioDias',
            'ocProveedores',
            'conteoOpinionPendiente',
            'conteoOpinionOk',
            'totalProveedores',
            'conteoProveedoresActivos',
            'conteoInventarioCritico',
            'conteoOcAtrasadas',
        ));
    }

    public function crearOC(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores_users,id',
            'productos_oc' => 'required|array|min:1',
            'productos_oc.*.producto_id' => 'required|exists:productos,id',
            'productos_oc.*.cantidad' => 'required|numeric|min:0.01',
            'fecha_entrega' => 'required|date|after:today',
        ]);

        $proveedor = ProveedorUser::find($request->proveedor_id);
        $productosOC = [];
        $montoTotal = 0;

        foreach ($request->productos_oc as $item) {
            $producto = Producto::find($item['producto_id']);
            if ($producto) {
                $monto = $producto->precio * $item['cantidad'];
                $montoTotal += $monto;
                $productosOC[] = [
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'cantidad' => $item['cantidad'],
                    'precio' => $producto->precio,
                    'monto' => $monto,
                ];
            }
        }

        OcBorrador::create([
            'tipo' => 'manual',
            'proveedor_id' => $request->proveedor_id,
            'productos' => $productosOC,
            'monto_estimado' => $montoTotal,
            'motivo' => 'OC generada manualmente desde Gestión de Compras',
            'estatus' => 'pendiente',
            'notas' => 'Fecha entrega esperada: '.$request->fecha_entrega,
        ]);

        return back()->with('mensaje', "✅ OC creada para {$proveedor->nombre} con ".count($productosOC).' producto(s). Monto: $'.number_format($montoTotal, 2));
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

    // ── Exportaciones de Gestión de Compras ──

    public function exportOpinion()
    {
        $proveedores = ProveedorUser::where('activo', true)->orderBy('nombre')->get();
        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['OPINION POSITIVA SAT - ESTADO POR PROVEEDOR'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['ID SISTEMA', 'ID PROVEEDOR', 'PROVEEDOR', 'CORREO', 'ESTADO OPINION']];

        foreach ($proveedores as $prov) {
            $doc = DocumentoProveedor::where('proveedor_id', $prov->id)->where('tipo', 'opinion')->latest()->first();
            $est = $doc ? $doc->estatus : 'Sin documento';
            $labels = ['aprobado' => 'Positiva', 'pendiente' => 'En revision', 'rechazado' => 'Negativa'];
            $lines[] = [$prov->id, $prov->id_proveedor ?? '—', $prov->nombre ?? $prov->usuario, $prov->correo ?? '-', $labels[$est] ?? $est];
        }

        return $this->csvResponse($lines, 'Opinion_Positiva_'.now()->format('Y-m-d').'.csv');
    }

    public function exportAutorizacion()
    {
        $proveedores = ProveedorUser::orderBy('nombre')->get();
        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['AUTORIZACION DE PROVEEDORES'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['ID SISTEMA', 'ID PROVEEDOR', 'PROVEEDOR', 'CORREO', 'ESTADO', 'SCORE']];

        foreach ($proveedores as $prov) {
            $lines[] = [$prov->id, $prov->id_proveedor ?? '—', $prov->nombre ?? $prov->usuario, $prov->correo ?? '-', $prov->activo ? 'Activo' : 'Inactivo', $prov->score_total.'%'];
        }

        return $this->csvResponse($lines, 'Autorizacion_Proveedores_'.now()->format('Y-m-d').'.csv');
    }

    public function exportDiasInventario()
    {
        $inventarioService = app(InventarioCalculoService::class);
        $diasPedido = (int) AlertaConfiguracion::get('dias_alerta_documento', 7);
        $reporte = $inventarioService->generarReporteCompleto();
        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['DIAS DE INVENTARIO POR ARTICULO'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['CODIGO', 'PRODUCTO', 'STOCK', 'UNIDAD', 'DIAS INVENTARIO', 'DIAS PEDIDO', 'DIAS ENTREGA', 'ESTADO']];

        foreach ($reporte as $row) {
            $umbralReorden = $diasPedido + $row['dias_entrega'];
            $estadoLabels = [
                'agotado' => 'AGOTADO',
                'bajo_minimo' => 'REORDENAR',
                'sobre_stock' => 'EXCESO',
                'ok' => 'OK',
            ];
            $estado = $estadoLabels[$row['estado']] ?? ($row['dias_inventario'] < $umbralReorden ? 'REORDENAR' : 'OK');
            $lines[] = [
                $row['codigo'],
                $row['nombre'],
                number_format((int) $row['existencia']),
                $row['unidad'],
                $row['dias_inventario'].' dias',
                $diasPedido.' dias',
                $row['dias_entrega'].' dias',
                $estado,
            ];
        }

        return $this->csvResponse($lines, 'Dias_Inventario_'.now()->format('Y-m-d').'.csv');
    }

    public function exportCostos()
    {
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();
        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['COSTOS DE PRODUCTOS'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['CODIGO', 'PRODUCTO', 'CATEGORIA', 'PRECIO ACTUAL', 'UNIDAD', 'STOCK']];

        foreach ($productos as $prod) {
            $lines[] = [$prod->codigo, $prod->nombre, $prod->categoria ?? '-', '$'.number_format((float) $prod->precio, 2), $prod->unidad_venta, number_format((int) $prod->stock)];
        }

        return $this->csvResponse($lines, 'Costos_Productos_'.now()->format('Y-m-d').'.csv');
    }

    private function csvResponse(array $lines, string $filename)
    {
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

    public function facturasExcel(Request $request)
    {
        $facturas = $this->queryFacturasFiltradas($request)
            ->orderBy('created_at', 'desc')
            ->get();

        $lines = [['INDUSTRIAS SALCOM S.A. DE C.V.'], ['REPORTE DE FACTURAS DE PROVEEDORES'], ['Generado: '.now()->format('d/m/Y H:i')], [], ['FOLIO CFDI', 'PROVEEDOR', 'CÓDIGO', 'MONTO', 'IVA', 'TOTAL', 'ESTATUS', 'VENCIMIENTO', 'DÍAS VENCIDO']];

        foreach ($facturas as $f) {
            $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
            $diasV = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;
            $lines[] = [
                $f->folio_cfdi,
                $f->proveedor !== null ? ($f->proveedor->nombre ?? $f->codigo_proveedor) : $f->codigo_proveedor,
                $f->codigo_proveedor,
                '$'.number_format((float) $f->monto, 2),
                '$'.number_format((float) $f->monto_iva, 2),
                '$'.number_format((float) $f->total, 2),
                ucfirst($f->estatus),
                $f->fecha_vencimiento?->format('d/m/Y') ?? '-',
                $vencida ? $diasV.' días' : '-',
            ];
        }
        $lines[] = [];
        $lines[] = ['', '', '', '', '', 'TOTAL:', '$'.number_format((float) $facturas->sum('total'), 2)];

        return $this->csvResponse($lines, 'Facturas_Proveedores_'.now()->format('Y-m-d').'.csv');
    }

    public function autorizarProveedor(Request $request)
    {
        $request->validate(['proveedor_id' => 'required', 'accion' => 'required|in:alta,baja']);

        $prov = ProveedorUser::with('contactos')->findOrFail($request->proveedor_id);

        if ($request->accion === 'alta' && ! $prov->contactosSuficientes()) {
            return back()->with('error', 'No se puede dar de alta: el proveedor debe tener mínimo 2 contactos registrados.');
        }

        $prov->update(['activo' => $request->accion === 'alta']);

        return back()->with('mensaje', "Proveedor {$prov->nombre} ".($request->accion === 'alta' ? 'dado de alta' : 'dado de baja').' por dirección.');
    }

    /** Solicitudes de alta (onboarding): proveedores inactivos para revisión manual de Contabilidad/Dirección. */
    public function solicitudesAlta(Request $request)
    {
        $filtro = $request->input('filtro', 'todas'); // todas | con_datos | sin_datos

        // Criterio: inactivo + formulario bancario + al menos 1 documento aprobado (validación hecha).
        // Si fue rechazado y aún no reenvía formulario, no tiene bancarios → no aparece.
        $pendientes = ProveedorUser::with(['documentos', 'contactos'])
            ->where('activo', false)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProveedorUser $p) {
                $formulario = $p->tieneFormularioIdentificacion();
                $bancarios = $p->tieneFormularioDatosBancarios();
                $docsOk = $p->documentosFiscalesCompletos();
                $docsAprobados = $p->documentos->where('estatus', 'aprobado');
                $docsCount = $docsAprobados->count();
                $tieneValidacion = $docsCount > 0;
                $contactosN = $p->contactos->count();
                $listo = $p->listoParaDireccion();
                $conDatos = $bancarios && $tieneValidacion;

                return (object) [
                    'proveedor' => $p,
                    'formulario' => $formulario,
                    'bancarios' => $bancarios,
                    'docs_count' => $docsCount,
                    'docs_ok' => $docsOk,
                    'num_contactos' => $contactosN,
                    'listo' => $listo,
                    'con_datos' => $conDatos,
                ];
            })
            ->filter(fn ($item) => $item->con_datos)
            ->values();

        $conteoConDatos = $pendientes->count();
        $conteoSinDatos = 0;

        if ($filtro === 'sin_datos') {
            $pendientes = collect();
        }

        // Cargar solicitudes de la tabla nueva (si existe)
        $solicitudes = collect();
        try {
            $solicitudes = SolicitudAlta::with('proveedor')
                ->where('estatus', '!=', 'rechazada')
                ->orderByDesc('created_at')
                ->get();
        } catch (\Exception $e) {
            // La tabla no existe aún en este servidor
        }

        return response()
            ->view('admin.solicitudes-alta', compact(
                'pendientes',
                'filtro',
                'conteoConDatos',
                'conteoSinDatos',
                'solicitudes'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function aprobarSolicitudAlta(Request $request)
    {
        $request->validate(['proveedor_id' => 'required|integer']);

        $prov = ProveedorUser::with('contactos')->findOrFail($request->proveedor_id);

        if ($prov->activo) {
            return $this->respuestaSolicitudAlta($request, 'Este proveedor ya está activo.', false);
        }

        if (! $prov->contactosSuficientes()) {
            return $this->respuestaSolicitudAlta($request, 'No se puede aprobar: faltan contactos. El proveedor debe registrar mínimo 2 contactos.', false);
        }

        if (! $prov->tieneFormularioDatosBancarios()) {
            return $this->respuestaSolicitudAlta($request, 'No se puede aprobar: faltan datos bancarios.', false);
        }

        if (! $prov->documentosFiscalesCompletos()) {
            return $this->respuestaSolicitudAlta($request, 'No se puede aprobar: faltan documentos fiscales aprobados.', false);
        }

        $prov->update(['activo' => true]);

        if (Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
            $prov->update(['solicitud_alta_estatus' => 'aprobada']);
        }

        try {
            SolicitudAlta::where('proveedor_id', $prov->id)->update(['estatus' => 'aprobada']);
        } catch (\Exception $e) {
            // ignore
        }

        $nombre = $prov->nombre ?? $prov->usuario;
        $titulo = 'Tu solicitud de alta fue aprobada';
        $contenido = 'Hola '.$nombre.'. Tu solicitud de alta fue aceptada. Ya puedes iniciar sesión y navegar el Portal de Proveedores completo.';

        try {
            app(AlertEngineService::class)->alertar([
                'tipo' => 'solicitud_aprobada',
                'modulo' => 'onboarding',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov->id,
                'titulo' => $titulo,
                'contenido' => $contenido,
                'nivel' => 'info',
            ], 'portal');
        } catch (\Exception $e) {
            Log::warning('No se pudo notificar aprobación de solicitud', [
                'proveedor_id' => $prov->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (! empty($prov->correo)) {
            try {
                Mail::to($prov->correo)->send(new SolicitudAltaAprobada(
                    $nombre,
                    $prov->correo,
                    (string) ($prov->usuario ?? ''),
                    route('proveedores.login'),
                ));
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar correo de solicitud de alta aprobada', [
                    'proveedor_id' => $prov->id,
                    'correo' => $prov->correo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->respuestaSolicitudAlta(
            $request,
            "Proveedor {$prov->nombre} aprobado y activado. Ya puede usar el portal completo."
        );
    }

    public function rechazarSolicitudAlta(Request $request)
    {
        $request->validate(['proveedor_id' => 'required|integer']);

        $prov = ProveedorUser::findOrFail($request->proveedor_id);

        if ($prov->activo) {
            return $this->respuestaSolicitudAlta(
                $request,
                'Este proveedor ya está activo; no se puede rechazar como solicitud pendiente.',
                false
            );
        }

        $notas = $request->input('notas', 'Rechazado desde panel de solicitudes de alta. Debe volver a completar formulario y documentos.');

        try {
            SolicitudAlta::updateOrCreate(
                ['proveedor_id' => $prov->id],
                [
                    'estatus' => 'rechazada',
                    'notas_admin' => $notas,
                    'tipo_persona' => $prov->tipo_persona ?? 'Persona Moral',
                    'nombre_completo' => $prov->nombre ?? $prov->usuario,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('No se pudo marcar SolicitudAlta como rechazada', [
                'proveedor_id' => $prov->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Marca en el proveedor (no depende de la tabla solicitudes_alta).
        $updateProv = [
            'activo' => false,
            'datos_identificacion' => null,
        ];
        if (Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
            $updateProv['solicitud_alta_estatus'] = 'rechazada';
        }
        $prov->update($updateProv);

        try {
            DocumentoProveedor::where('proveedor_id', $prov->id)->update([
                'estatus' => 'rechazado',
                'notas_revision' => 'Invalidado por rechazo de solicitud de alta. Debe volver a validar documentos.',
                'revisado_at' => null,
                'resultado_validacion' => null,
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        $nombre = $prov->nombre ?? $prov->usuario;
        $titulo = 'Tu solicitud de alta fue rechazada';
        $contenido = "Hola {$nombre}. Tu solicitud fue rechazada. Debes volver a completar datos bancarios y documentos para que Dirección la revise de nuevo.";

        try {
            app(AlertEngineService::class)->alertar([
                'tipo' => 'solicitud_rechazada',
                'modulo' => 'onboarding',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $prov->id,
                'titulo' => $titulo,
                'contenido' => $contenido,
                'nivel' => 'warning',
            ], 'portal');
        } catch (\Exception $e) {
            Log::warning('No se pudo notificar rechazo de solicitud de alta', [
                'proveedor_id' => $prov->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->respuestaSolicitudAlta(
            $request,
            "Solicitud de {$nombre} rechazada. La cuenta no se elimina: debe volver a llenar datos bancarios y documentos."
        );
    }

    /** Respuesta JSON (AJAX) o redirect con anti-caché. */
    private function respuestaSolicitudAlta(Request $request, string $mensaje, bool $ok = true)
    {
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => $ok,
                'mensaje' => $mensaje,
            ], $ok ? 200 : 422);
        }

        $redirect = redirect()
            ->route('admin.solicitudes-alta')
            ->with($ok ? 'mensaje' : 'error', $mensaje)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');

        return $redirect;
    }

    /** Solo documentos ya validados correctamente (aprobados) + datos del formulario. */
    public function verDocumentosAprobadosSolicitud(ProveedorUser $proveedor)
    {
        $tiposLabel = [
            'cif' => 'Constancia de Situación Fiscal (CIF)',
            'opinion' => 'Opinión de Cumplimiento SAT',
            'acta' => 'Acta Constitutiva',
            'rep_legal' => 'ID Representante Legal',
            'contribuyente' => 'ID Contribuyente',
            'caratula_banco' => 'Carátula de Banco',
            'poder' => 'Poder Notarial',
        ];

        $docsAprobados = $proveedor->documentos()
            ->where('estatus', 'aprobado')
            ->orderBy('tipo')
            ->get();

        $datosIdent = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
        $proveedor->load('contactos');

        return view('admin.solicitud-docs-ver', compact(
            'proveedor',
            'docsAprobados',
            'tiposLabel',
            'datosIdent'
        ));
    }

    /** Solicitudes de actualización/renovación de docs de proveedores activos. */
    public function solicitudesActualizacionDocs(Request $request)
    {
        $estatus = $request->input('estatus', 'pendiente');
        $query = SolicitudModificacionDatos::with('proveedor')->orderByDesc('id');
        if (in_array($estatus, ['pendiente', 'aprobada', 'rechazada'], true)) {
            $query->where('estatus', $estatus);
        }
        // estatus=all → sin filtro
        $solicitudes = $query->paginate(30)->withQueryString();

        return view('admin.solicitudes-actualizacion-docs', compact('solicitudes', 'estatus'));
    }

    public function marcarSolicitudActualizacionDocs(Request $request)
    {
        $request->validate([
            'solicitud_id' => 'required|integer',
            'accion' => 'required|in:aprobar,rechazar',
            'notas' => 'nullable|string|max:1000',
        ]);

        $sol = SolicitudModificacionDatos::findOrFail($request->solicitud_id);
        $sol->update([
            'estatus' => $request->accion === 'aprobar' ? 'aprobada' : 'rechazada',
            'notas' => $request->input('notas', $sol->notas),
            'revisado_at' => now(),
        ]);

        // Si hay nombre distinto en la propuesta y se aprueba → aplicar.
        if ($request->accion === 'aprobar'
            && $sol->campo !== 'documentos_fiscales'
            && filled($sol->valor_propuesto)
            && $sol->proveedor) {
            $sol->proveedor->update(['nombre' => $sol->valor_propuesto]);
        }

        return back()->with('mensaje', 'Solicitud marcada como '.($request->accion === 'aprobar' ? 'aprobada' : 'rechazada').'.');
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
        $codigos = $proveedores->pluck('id_proveedor')->filter()->values();
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
            $codigo = $prov->id_proveedor;
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
            $forecast = $otifActual > 0 ? $otifActual : (float) $prov->score_total;
            $forecastAnterior = $otifAnterior;

            $metricas[$prov->id] = [
                'otif_actual' => $otifActual,
                'forecast' => $forecast,
                'compras_trim' => $comprasTrim,
                'estimado' => $comprasTrim > 0 ? round($comprasTrim / 3, 2) : 0,
                'score_class' => $this->scoreBarClass($otifActual > 0 ? $otifActual : (float) $prov->score_total),
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
        return [
            'otif_actual' => 0,
            'forecast' => (float) $prov->score_total,
            'compras_trim' => 0,
            'estimado' => 0,
            'score_class' => $this->scoreBarClass((float) $prov->score_total),
            'forecast_class' => $this->scoreBarClass((float) $prov->score_total),
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

    private function calcularVariacionPct(float $actual, float $anterior): ?int
    {
        if ($anterior <= 0) {
            return $actual > 0 ? 100 : null;
        }

        return (int) round((($actual - $anterior) / $anterior) * 100);
    }

    private function calcularOtifResumen($facturasProveedor = null): array
    {
        $facturas = $facturasProveedor ?? Factura::whereNotNull('codigo_proveedor')->get();
        $total = $facturas->count();
        $pagadas = $facturas->where('estatus', 'pagada')->count();
        $canceladas = $facturas->where('estatus', 'cancelada')->count();

        return [
            'otPercent' => $total > 0 ? round(($pagadas / $total) * 100, 1) : 0,
            'ifPercent' => $total > 0 ? round((($total - $canceladas) / $total) * 100, 1) : 0,
        ];
    }

    private function calcularResumenFiscal(): array
    {
        $proveedores = ProveedorUser::where('activo', true)->get();
        $verde = $amarillo = $rojo = $gris = 0;

        foreach ($proveedores as $prov) {
            $docs = DocumentoProveedor::where('proveedor_id', $prov->id)->get();
            $aprobados = $docs->where('estatus', 'aprobado')->count();
            $rechazados = $docs->where('estatus', 'rechazado')->count();
            $totalSubidos = $docs->count();

            if ($aprobados >= 3 && $rechazados === 0) {
                $verde++;
            } elseif ($totalSubidos > 0 && $rechazados === 0) {
                $amarillo++;
            } elseif ($totalSubidos === 0) {
                $gris++;
            } else {
                $rojo++;
            }
        }

        $total = $proveedores->count();

        return [
            'verde' => $verde,
            'amarillo' => $amarillo,
            'rojo' => $rojo,
            'gris' => $gris,
            'total' => $total,
            'pctCumple' => $total > 0 ? round(($verde / $total) * 100) : 0,
        ];
    }

    private function parseProveedorIdBusqueda(?string $term): ?int
    {
        if ($term === null || trim($term) === '') {
            return null;
        }

        $limpio = ltrim(trim($term), '#');

        return ctype_digit($limpio) ? (int) $limpio : null;
    }

    // ── Solicitudes de Alta (métodos de acción) ──

    public function expedienteFiscal(Request $request)
    {
        $tipos = [
            'cif' => 'CIF / Constancia fiscal',
            'opinion' => 'Opinión SAT',
            'acta' => 'Acta constitutiva',
            'rep_legal' => 'INE Rep. legal',
            'contribuyente' => 'INE Contribuyente',
            'caratula_banco' => 'Carátula bancaria',
            'poder' => 'Poder notarial',
        ];

        $query = DocumentoProveedor::with('proveedor')->orderByDesc('created_at');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('busqueda')) {
            $b = $request->busqueda;
            $query->whereHas('proveedor', function ($q) use ($b) {
                $q->where('nombre', 'like', "%{$b}%")
                    ->orWhere('usuario', 'like', "%{$b}%")
                    ->orWhere('id_proveedor', 'like', "%{$b}%");
            });
        }

        if ($request->filled('persona')) {
            $persona = $request->persona;
            $query->whereHas('proveedor', function ($q) use ($persona) {
                if ($persona === 'fisica') {
                    $q->where('tipo_persona', 'like', '%Física%')
                        ->orWhere('tipo_persona', 'like', '%fisica%');
                } else {
                    $q->where('tipo_persona', 'like', '%Moral%')
                        ->orWhere('tipo_persona', 'like', '%moral%');
                }
            });
        }

        if ($request->filled('mes') && preg_match('/^\d{4}-\d{2}$/', (string) $request->mes)) {
            [$anio, $mesNum] = explode('-', (string) $request->mes);
            $query->whereYear('created_at', (int) $anio)->whereMonth('created_at', (int) $mesNum);
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        $documentos = $query->get();

        $mesesDisponibles = DocumentoProveedor::query()
            ->whereNotNull('created_at')
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->values();

        // Una fila por proveedor (lista plana, sin agrupar por mes aquí)
        $proveedoresConDocs = $documentos->groupBy('proveedor_id')->map(function ($docs) {
            $prov = $docs->first()->proveedor;
            if (! $prov) {
                return null;
            }
            $ultimo = $docs->sortByDesc('created_at')->first();
            $aprobados = $docs->where('estatus', 'aprobado')->count();
            $pendientes = $docs->where('estatus', 'pendiente')->count();
            $rechazados = $docs->where('estatus', 'rechazado')->count();

            return [
                'proveedor' => $prov,
                'documentos' => $docs,
                'total' => $docs->count(),
                'aprobados' => $aprobados,
                'pendientes' => $pendientes,
                'rechazados' => $rechazados,
                'ultimo_at' => $ultimo?->created_at,
                'meses' => $docs->map(fn ($d) => $d->created_at?->format('Y-m'))->filter()->unique()->count(),
            ];
        })->filter()->sortBy(fn ($item) => mb_strtoupper($item['proveedor']->nombre ?? $item['proveedor']->usuario ?? ''))->values();

        return view('admin.expediente-fiscal', compact('proveedoresConDocs', 'tipos', 'mesesDisponibles'));
    }

    public function expedienteFiscalVer(Request $request, ProveedorUser $proveedor)
    {
        $tiposLabel = [
            'cif' => 'Constancia de Situación Fiscal (CIF)',
            'opinion' => 'Opinión de Cumplimiento SAT',
            'acta' => 'Acta Constitutiva',
            'rep_legal' => 'ID Representante Legal',
            'contribuyente' => 'ID Contribuyente',
            'caratula_banco' => 'Carátula de Banco',
            'poder' => 'Poder Notarial',
        ];

        $docsQuery = $proveedor->documentos()->orderByDesc('created_at');

        if ($request->filled('tipo')) {
            $docsQuery->where('tipo', $request->tipo);
        }
        if ($request->filled('estatus')) {
            $docsQuery->where('estatus', $request->estatus);
        }
        if ($request->filled('mes') && preg_match('/^\d{4}-\d{2}$/', (string) $request->mes)) {
            [$anio, $mesNum] = explode('-', (string) $request->mes);
            $docsQuery->whereYear('created_at', (int) $anio)->whereMonth('created_at', (int) $mesNum);
        }

        $docs = $docsQuery->get();

        // Dentro del proveedor: expedientes agrupados mes por mes (más reciente primero)
        /** @var Collection<int, DocumentoProveedor> $docs */
        $docsPorMes = $docs->groupBy(fn (DocumentoProveedor $d) => $d->created_at?->format('Y-m') ?? '0000-00')
            ->sortKeysDesc();

        $proveedor->load('contactos');
        $datosIdent = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
        $filtrosQuery = $request->only(['busqueda', 'persona', 'tipo', 'mes', 'estatus']);

        return view('admin.expediente-fiscal-ver', compact(
            'proveedor',
            'docs',
            'docsPorMes',
            'tiposLabel',
            'datosIdent',
            'filtrosQuery'
        ));
    }

    public function descargarDocumentoFiscal(DocumentoProveedor $documento)
    {
        $archivo = ltrim((string) $documento->archivo, '/');
        $candidates = [
            storage_path('app/public/'.$archivo),
            storage_path('app/'.$archivo),
        ];

        $path = null;
        foreach ($candidates as $candidate) {
            if ($archivo !== '' && is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (! $path) {
            return back()->with('error', 'El archivo no se encontró en el servidor.');
        }

        $nombreArchivo = ($documento->proveedor?->nombre ?? 'proveedor').'_'.$documento->tipo.'.pdf';

        return response()->download($path, $nombreArchivo);
    }

    // ── Solicitudes de Alta (aprobar/rechazar) ──

    public function aprobarSolicitud(SolicitudAlta $solicitud)
    {
        $solicitud->update(['estatus' => 'aprobada']);

        return redirect()->route('admin.solicitudes-alta')->with('mensaje', 'Solicitud aprobada correctamente.');
    }

    public function revisarSolicitud(ProveedorUser $proveedor)
    {
        $proveedor->load('documentos');
        $datosIdent = $proveedor->datos_identificacion ?? [];

        $identificacion = [
            'tipo_persona' => $proveedor->tipo_persona ?? ($datosIdent['tipo_persona'] ?? null),
            'tipo_clave' => str_contains(strtolower($proveedor->tipo_persona ?? ''), 'moral') ? 'moral' : 'fisica',
            'nombre_esperado' => $datosIdent['nombre_esperado'] ?? $proveedor->nombre,
            'razon_social' => $datosIdent['razon_social'] ?? null,
            'apellido_paterno' => $datosIdent['apellido_paterno'] ?? null,
            'apellido_materno' => $datosIdent['apellido_materno'] ?? null,
            'nombres' => $datosIdent['nombres'] ?? null,
            'clabe' => $datosIdent['clabe'] ?? null,
            'cuenta' => $datosIdent['cuenta'] ?? null,
            'banco' => $datosIdent['banco'] ?? null,
            'cp' => $datosIdent['cp'] ?? null,
            'correo' => $proveedor->correo ?? ($datosIdent['correo'] ?? null),
        ];

        $solicitudId = $proveedor->id;

        return view('APIS.empresa', compact('identificacion', 'solicitudId'));
    }

    public function rechazarSolicitud(Request $request, SolicitudAlta $solicitud)
    {
        $solicitud->update([
            'estatus' => 'rechazada',
            'notas_admin' => $request->input('notas', ''),
        ]);

        return redirect()->route('admin.solicitudes-alta')->with('mensaje', 'Solicitud rechazada.');
    }

    public function reembolsos()
    {
        $reembolsos = Alerta::where('tipo', 'solicitud_reembolso')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Verificar si hay registros en bitácora de gasolina (para habilitar reembolso de gasolina)
        $tieneBitacoraGasolina = Alerta::where('tipo', 'bitacora_gasolina')->exists();

        return view('admin.reembolsos', compact('reembolsos', 'tieneBitacoraGasolina'));
    }

    public function enviarReembolso(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string|in:gasto_general,gasolina,computo,viaticos_nacional,viaticos_internacional',
            'razon_social' => 'required|string|in:Industrias Salcom S.A. de C.V.,Franfoods S.A. de C.V.',
            'metodo_pago_empresa' => 'required|string|in:bbva,inntec',
            'monto' => 'required|string|max:20',
            'concepto' => 'required|string|max:255',
            'solicitante' => 'required|string|max:150',
            'numero_cuenta' => 'required|string|max:20',
            'titular_cuenta' => 'required|string|max:150',
            'fecha_factura' => 'required|date',
            'archivo_factura' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'archivo_xml' => 'nullable|file|max:5120',
            'archivo_materialidad' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notas' => 'nullable|string|max:500',
        ], [
            'archivo_factura.required' => 'Debes subir la factura o ticket.',
            'numero_cuenta.required' => 'El número de cuenta es obligatorio.',
            'titular_cuenta.required' => 'El nombre del titular es obligatorio.',
            'archivo_factura.mimes' => 'Solo PDF, JPG o PNG.',
            'archivo_factura.max' => 'Máximo 10 MB.',
            'archivo_materialidad.mimes' => 'La materialidad debe ser PDF, JPG o PNG.',
            'archivo_materialidad.max' => 'Máximo 10 MB.',
            'solicitante.required' => 'Indica quién solicita el reembolso.',
        ]);

        // Validar que gasolina tenga bitácora previa
        if ($request->input('categoria') === 'gasolina') {
            $tieneBitacora = Alerta::where('tipo', 'bitacora_gasolina')->exists();
            if (! $tieneBitacora) {
                return back()->withErrors(['categoria' => 'Para reembolso de gasolina debes llenar primero la Bitácora de Gasolina.'])->withInput();
            }
        }

        // Validar materialidad obligatoria si no es Inntec
        if ($request->input('metodo_pago_empresa') !== 'inntec' && ! $request->hasFile('archivo_materialidad')) {
            return back()->withErrors(['archivo_materialidad' => 'La materialidad (correo o foto) es obligatoria. Sin materialidad el reembolso se rechaza.'])->withInput();
        }

        $pathFactura = $request->file('archivo_factura')->store('reembolsos/facturas', 'public');
        $pathXml = $request->hasFile('archivo_xml')
            ? $request->file('archivo_xml')->store('reembolsos/xml', 'public')
            : null;
        $pathMaterialidad = $request->hasFile('archivo_materialidad')
            ? $request->file('archivo_materialidad')->store('reembolsos/materialidad', 'public')
            : null;

        try {
            Alerta::create([
                'tipo' => 'solicitud_reembolso',
                'modulo' => 'reembolsos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 0,
                'titulo' => 'Reembolso: $' . $request->input('monto') . ' — ' . $request->input('solicitante'),
                'contenido' => $request->input('concepto') . ' (' . $request->input('categoria') . ')',
                'datos' => [
                    'categoria' => $request->input('categoria'),
                    'razon_social' => $request->input('razon_social'),
                    'metodo_pago_empresa' => $request->input('metodo_pago_empresa'),
                    'uso_cfdi' => $request->input('uso_cfdi'),
                    'forma_pago' => $request->input('forma_pago'),
                    'metodo_pago' => $request->input('metodo_pago'),
                    'monto' => $request->input('monto'),
                    'concepto' => $request->input('concepto'),
                    'solicitante' => $request->input('solicitante'),
                    'numero_cuenta' => $request->input('numero_cuenta'),
                    'titular_cuenta' => $request->input('titular_cuenta'),
                    'fecha_factura' => $request->input('fecha_factura'),
                    'archivo_factura' => $pathFactura,
                    'archivo_xml' => $pathXml,
                    'archivo_materialidad' => $pathMaterialidad,
                    'notas' => $request->input('notas'),
                ],
                'estatus' => 'pendiente',
                'nivel' => 'info',
            ]);
        } catch (\Exception $e) {
        }

        return redirect()->route('admin.reembolsos')
            ->with('mensaje', 'Reembolso registrado correctamente.');
    }

    public function reembolsosExcel()
    {
        $reembolsos = Alerta::where('tipo', 'solicitud_reembolso')
            ->orderByDesc('created_at')
            ->get();

        $output = "\xEF\xBB\xBF";
        $output .= "Fecha,Solicitante,Concepto,Monto,Numero de Cuenta,Titular de la Tarjeta,Institucion (BBVA/Inntec),Categoria,Autorizado Sandra\r\n";

        foreach ($reembolsos as $r) {
            $d = $r->datos ?? [];
            $output .= implode(',', [
                $r->created_at?->format('d/m/Y') ?? '',
                '"' . str_replace('"', '""', $d['solicitante'] ?? '') . '"',
                '"' . str_replace('"', '""', $d['concepto'] ?? '') . '"',
                '"' . ($d['monto'] ?? '0') . '"',
                '"' . ($d['numero_cuenta'] ?? '') . '"',
                '"' . str_replace('"', '""', $d['titular_cuenta'] ?? '') . '"',
                strtoupper($d['metodo_pago_empresa'] ?? ''),
                $d['categoria'] ?? '',
                !empty($d['autorizado_sandra']) ? 'SI' : 'NO',
            ]) . "\r\n";
        }

        $filename = 'Reembolsos_' . now()->format('Y-m-d') . '.csv';

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function autorizarReembolso(Alerta $alerta)
    {
        $datos = $alerta->datos ?? [];
        $datos['autorizado_sandra'] = true;
        $datos['autorizado_sandra_fecha'] = now()->format('Y-m-d H:i');
        $alerta->update(['datos' => $datos]);

        return redirect()->route('admin.reembolsos')
            ->with('mensaje', 'Reembolso autorizado por Sandra Gutiérrez.');
    }

    public function bitacoraGasolina(Request $request)
    {
        try {
            $query = Alerta::where('tipo', 'bitacora_gasolina')
                ->orderByDesc('created_at');

            if ($request->filled('filtro_empleado')) {
                $filtro = $request->input('filtro_empleado');
                // Buscar en JSON — compatible con MySQL 5.7+ y 8.0
                $query->where(function ($q) use ($filtro) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(datos, '$.numero_empleado')) LIKE ?", ["%{$filtro}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(datos, '$.empleado')) LIKE ?", ["%{$filtro}%"]);
                });
            }

            $registros = $query->limit(100)->get();
        } catch (\Exception $e) {
            // Fallback si JSON queries fallan: traer todo y filtrar en PHP
            $all = Alerta::where('tipo', 'bitacora_gasolina')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            if ($request->filled('filtro_empleado')) {
                $filtro = strtolower($request->input('filtro_empleado'));
                $registros = $all->filter(function ($r) use ($filtro) {
                    $d = $r->datos ?? [];
                    return str_contains(strtolower($d['numero_empleado'] ?? ''), $filtro)
                        || str_contains(strtolower($d['empleado'] ?? ''), $filtro);
                });
            } else {
                $registros = $all;
            }
        }

        return view('admin.bitacora-gasolina', compact('registros'));
    }

    public function bitacoraGasolinaGuardar(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'numero_empleado' => 'required|string|max:50',
            'empleado' => 'required|string|max:150',
            'cantidad_litros' => 'nullable|numeric|min:0',
            'rendimiento' => 'nullable|numeric|min:0',
            'monto' => 'required|string|max:20',
            'vehiculo' => 'nullable|string|max:100',
            'kilometraje' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:255',
            'factura_gasolina' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $pathFactura = $request->hasFile('factura_gasolina')
            ? $request->file('factura_gasolina')->store('bitacora-gasolina', 'public')
            : null;

        try {
            Alerta::create([
                'tipo' => 'bitacora_gasolina',
                'modulo' => 'gasolina',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 0,
                'titulo' => 'Gasolina: $' . $request->input('monto') . ' — ' . $request->input('empleado'),
                'contenido' => ($request->input('vehiculo') ?? '') . ' | ' . $request->input('fecha'),
                'datos' => [
                    'fecha' => $request->input('fecha'),
                    'numero_empleado' => $request->input('numero_empleado'),
                    'empleado' => $request->input('empleado'),
                    'cantidad_litros' => $request->input('cantidad_litros'),
                    'rendimiento' => $request->input('rendimiento'),
                    'monto' => $request->input('monto'),
                    'vehiculo' => $request->input('vehiculo'),
                    'kilometraje' => $request->input('kilometraje'),
                    'notas' => $request->input('notas'),
                    'factura' => $pathFactura,
                ],
                'estatus' => 'pendiente',
                'nivel' => 'info',
            ]);
        } catch (\Exception $e) {
        }

        return redirect()->route('admin.bitacora-gasolina')
            ->with('mensaje', 'Registro de gasolina guardado.');
    }

    public function bitacoraGasolinaExcel(Request $request)
    {
        try {
            $query = Alerta::where('tipo', 'bitacora_gasolina')
                ->orderByDesc('created_at');

            if ($request->filled('filtro_empleado')) {
                $filtro = $request->input('filtro_empleado');
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(datos, '$.numero_empleado')) LIKE ?", ["%{$filtro}%"]);
            }

            $registros = $query->get();
        } catch (\Exception $e) {
            $registros = Alerta::where('tipo', 'bitacora_gasolina')
                ->orderByDesc('created_at')
                ->get();
        }

        $output = "\xEF\xBB\xBF";
        $output .= "BITACORA DE GASOLINA\r\n";
        $output .= "Generado: " . now()->format('d/m/Y H:i') . "\r\n\r\n";
        $output .= "Fecha,Numero Empleado,Empleado,Litros,Monto,Vehiculo,Kilometraje,Notas\r\n";

        $totalMonto = 0;
        foreach ($registros as $r) {
            $d = $r->datos ?? [];
            $monto = (float) str_replace(['$', ','], '', $d['monto'] ?? '0');
            $totalMonto += $monto;
            $output .= implode(',', [
                $d['fecha'] ?? $r->created_at->format('Y-m-d'),
                '"' . str_replace('"', '""', $d['numero_empleado'] ?? '') . '"',
                '"' . str_replace('"', '""', $d['empleado'] ?? '') . '"',
                $d['cantidad_litros'] ?? '',
                number_format($monto, 2, '.', ''),
                '"' . str_replace('"', '""', $d['vehiculo'] ?? '') . '"',
                $d['kilometraje'] ?? '',
                '"' . str_replace('"', '""', $d['notas'] ?? '') . '"',
            ]) . "\r\n";
        }
        $output .= ",,,,,,,\r\n";
        $output .= ",,TOTAL,," . number_format($totalMonto, 2, '.', '') . ",,,\r\n";

        $filename = 'Bitacora_Gasolina_' . now()->format('Y-m-d') . '.csv';

        return response($output)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
