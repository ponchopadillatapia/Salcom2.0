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
        $data = [
            // Clientes
            'totalClientes'   => ClienteUser::count(),
            'clientesActivos' => ClienteUser::where('activo', true)->count(),

            // Proveedores
            'totalProveedores'   => ProveedorUser::count(),
            'proveedoresActivos' => ProveedorUser::where('activo', true)->count(),
            'scorePromedio'      => round((float) ProveedorUser::avg('score_total'), 1),

            // Pedidos
            'totalPedidos'     => Pedido::count(),
            'pedidosPendientes' => Pedido::whereIn('estatus', ['validacion', 'procesando'])->count(),
            'pedidosEntregados' => Pedido::where('estatus', 'entregado')->count(),
            'montoPedidos'     => Pedido::sum('total'),

            // Productos
            'totalProductos' => Producto::count(),
            'sinStock'       => Producto::where('stock', '<=', 0)->count(),

            // Facturas
            'facturasPendientes' => Factura::where('estatus', 'pendiente')->count(),
            'montoFacturas'      => Factura::where('estatus', 'pendiente')->sum('total'),

            // Encuestas
            'totalEncuestas'    => Encuesta::count(),
            'calificacionProm'  => round((float) Encuesta::avg('calificacion'), 1),

            // Muestras
            'muestrasActivas' => Muestra::whereNotIn('etapa', ['aprobado', 'rechazado'])->count(),

            // Documentos
            'docsPendientes' => DocumentoProveedor::where('estatus', 'pendiente')->count(),

            // Últimos pedidos
            'ultimosPedidos' => Pedido::orderBy('created_at', 'desc')->limit(5)->get(),

            // Top proveedores por score
            'topProveedores' => ProveedorUser::where('score_total', '>', 0)->orderBy('score_total', 'desc')->limit(5)->get(),
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

    // ── Encuestas ──

    public function encuestas()
    {
        $encuestas = Encuesta::orderBy('created_at', 'desc')->paginate(20);

        $promedioGeneral    = Encuesta::avg('calificacion');
        $promedioEntrega    = Encuesta::avg('tiempo_entrega');
        $promedioCalidad    = Encuesta::avg('calidad_producto');
        $totalEncuestas     = Encuesta::count();

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

        return view('admin.proveedores', compact('proveedores', 'busqueda'));
    }
}
