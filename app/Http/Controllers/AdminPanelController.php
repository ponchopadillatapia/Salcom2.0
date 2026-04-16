<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use App\Models\Encuesta;
use App\Models\Pedido;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
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
}
