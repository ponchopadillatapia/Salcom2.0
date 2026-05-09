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
        // ── Datos para gráficas ──

        // Pedidos por mes (últimos 6 meses)
        $pedidosPorMes = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $pedidosPorMes->push([
                'mes'   => $fecha->translatedFormat('M Y'),
                'total' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->count(),
                'monto' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->sum('total'),
            ]);
        }

        $pedidosPorEstatus = Pedido::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')->pluck('total', 'estatus');

        $facturasPorEstatus = Factura::selectRaw('estatus, count(*) as total, sum(total) as monto')
            ->groupBy('estatus')->get()->keyBy('estatus');

        $proveedoresScore = ProveedorUser::where('score_total', '>', 0)
            ->orderBy('score_total', 'desc')->limit(10)
            ->get(['nombre', 'usuario', 'score_entrega', 'score_puntualidad', 'score_total']);

        $clientesActivos   = ClienteUser::where('activo', true)->count();
        $clientesInactivos = ClienteUser::where('activo', false)->count();

        $proveedoresActivos   = ProveedorUser::where('activo', true)->count();
        $proveedoresInactivos = ProveedorUser::where('activo', false)->count();

        $encuestasPorCliente = Encuesta::selectRaw('codigo_cliente, avg(calificacion) as prom, count(*) as total')
            ->groupBy('codigo_cliente')->get();

        $muestrasPorEtapa = Muestra::selectRaw('etapa, count(*) as total')
            ->groupBy('etapa')->pluck('total', 'etapa');

        $data = [
            'totalClientes'      => ClienteUser::count(),
            'clientesActivos'    => $clientesActivos,
            'totalProveedores'   => ProveedorUser::count(),
            'proveedoresActivos' => $proveedoresActivos,
            'scorePromedio'      => round((float) ProveedorUser::avg('score_total'), 1),
            'totalPedidos'       => Pedido::count(),
            'pedidosPendientes'  => Pedido::whereIn('estatus', ['validacion', 'procesando'])->count(),
            'pedidosEntregados'  => Pedido::where('estatus', 'entregado')->count(),
            'montoPedidos'       => Pedido::sum('total'),
            'totalProductos'     => Producto::count(),
            'sinStock'           => Producto::where('stock', '<=', 0)->count(),
            'facturasPendientes' => Factura::where('estatus', 'pendiente')->count(),
            'montoFacturas'      => Factura::where('estatus', 'pendiente')->sum('total'),
            'totalEncuestas'     => Encuesta::count(),
            'calificacionProm'   => round((float) Encuesta::avg('calificacion'), 1),
            'muestrasActivas'    => Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])->count(),
            'docsPendientes'     => DocumentoProveedor::where('estatus', 'pendiente')->count(),
            'ultimosPedidos'     => Pedido::orderBy('created_at', 'desc')->limit(5)->get(),
            'topProveedores'     => ProveedorUser::where('score_total', '>', 0)->orderBy('score_total', 'desc')->limit(5)->get(),

            // Datos para gráficas
            'pedidosPorMes'        => $pedidosPorMes,
            'pedidosPorEstatus'    => $pedidosPorEstatus,
            'facturasPorEstatus'   => $facturasPorEstatus,
            'proveedoresScore'     => $proveedoresScore,
            'clientesInactivos'    => $clientesInactivos,
            'proveedoresInactivos' => $proveedoresInactivos,
            'encuestasPorCliente'  => $encuestasPorCliente,
            'muestrasPorEtapa'     => $muestrasPorEtapa,
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
        $cliente->update(['activo' => !$cliente->activo]);
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
        $totalEncuestas  = Encuesta::count();

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

        // Datos adicionales para las secciones de pedidos, productos y facturas
        $pedidos = Pedido::orderBy('created_at', 'desc')->limit(10)->get();
        $productos = Producto::where('activo', true)->orderBy('codigo')->limit(10)->get();
        $facturasPendientes = Factura::where('estatus', 'pendiente')->orderBy('fecha_vencimiento')->get();

        return view('admin.proveedores', compact('proveedores', 'busqueda', 'pedidos', 'productos', 'facturasPendientes'));
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

        // Facturas de clientes
        $queryClientes = Factura::whereNotNull('codigo_cliente');
        if ($estatus) $queryClientes->where('estatus', $estatus);
        if ($vencidas) $queryClientes->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        $facturasClientes = $queryClientes->orderBy('created_at', 'desc')->get();

        // Facturas de proveedores
        $queryProveedores = Factura::whereNotNull('codigo_proveedor');
        if ($estatus) $queryProveedores->where('estatus', $estatus);
        if ($vencidas) $queryProveedores->where('estatus', 'pendiente')->where('fecha_vencimiento', '<', now());
        $facturasProveedores = $queryProveedores->orderBy('created_at', 'desc')->get();

        return view('admin.facturas', compact('facturasClientes', 'facturasProveedores', 'estatus', 'vencidas'));
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
        $total       = Pedido::count();
        $entregados  = Pedido::where('estatus', 'entregado')->count();
        $enProceso   = Pedido::whereIn('estatus', ['validacion', 'procesando', 'enviado'])->count();
        $cancelados  = Pedido::where('estatus', 'cancelado')->count();
        $porcentaje  = $total > 0 ? round(($entregados / $total) * 100) : 0;

        $pedidos = Pedido::orderBy('created_at', 'desc')->limit(20)->get();

        return view('admin.otif', compact('total', 'entregados', 'enProceso', 'cancelados', 'porcentaje', 'pedidos'));
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
        $data = collect();
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $data->push([
                'mes'   => $fecha->translatedFormat('M Y'),
                'total' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->count(),
                'monto' => Pedido::whereYear('created_at', $fecha->year)->whereMonth('created_at', $fecha->month)->sum('total'),
            ]);
        }
        return $data;
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
