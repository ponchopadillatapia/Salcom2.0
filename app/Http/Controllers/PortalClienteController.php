<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use Illuminate\Http\Request;

class PortalClienteController extends Controller
{
    public function mostrarPortal() { return view('clientes.portal'); }
    public function mostrarDashboard() { return view('clientes.dashboard'); }
    public function mostrarCatalogo() { return view('clientes.catalogo'); }
    public function mostrarPedidos() { return view('clientes.pedidos'); }
    public function mostrarEstadoCuenta() { return view('clientes.estado-cuenta'); }

    public function mostrarPerfil()
    {
        $cliente = \App\Models\ClienteUser::find(session('cliente_id'));
        return view('clientes.perfil', ['cliente' => $cliente]);
    }

    public function mostrarTracking() { return view('clientes.tracking'); }
    public function mostrarEncuesta() { return view('clientes.encuesta'); }

    /**
     * Mapeo de valores de texto del formulario a tinyInteger para la BD.
     */
    private const TIEMPO_ENTREGA_MAP = [
        'rapido' => 1,
        'normal' => 2,
        'lento'  => 3,
    ];

    private const CALIDAD_PRODUCTO_MAP = [
        'excelente' => 1,
        'buena'     => 2,
        'regular'   => 3,
        'mala'      => 4,
    ];

    public function guardarEncuesta(Request $request)
    {
        $request->validate([
            'calificacion'    => 'required|integer|min:1|max:5',
            'tiempo_entrega'  => 'required|string|in:rapido,normal,lento',
            'calidad_producto'=> 'required|string|in:excelente,buena,regular,mala',
            'comentarios'     => 'nullable|string|max:2000',
            'pedido_id'       => 'nullable|integer',
        ]);

        Encuesta::create([
            'codigo_cliente'   => session('cliente_codigo'),
            'pedido_id'        => $request->input('pedido_id'),
            'calificacion'     => $request->input('calificacion'),
            'tiempo_entrega'   => self::TIEMPO_ENTREGA_MAP[$request->input('tiempo_entrega')],
            'calidad_producto' => self::CALIDAD_PRODUCTO_MAP[$request->input('calidad_producto')],
            'comentarios'      => $request->input('comentarios'),
        ]);

        return redirect()
            ->route('clientes.encuesta')
            ->with('encuesta_guardada', true);
    }
}
