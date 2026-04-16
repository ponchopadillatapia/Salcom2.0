<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use App\Models\Pedido;
use App\Models\TrackingPedido;
use App\Services\NotificacionService;
use App\Services\PaqueteriaService;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function __construct(
        private NotificacionService $notificaciones,
        private PaqueteriaService $paqueteria,
    ) {}

    /**
     * Cambia el estatus de un pedido y notifica al cliente
     * por BD + Email + WhatsApp automáticamente.
     */
    public function cambiarEstatus(Request $request, Pedido $pedido)
    {
        $request->validate([
            'estatus' => 'required|string|max:100',
            'notas'   => 'nullable|string|max:500',
        ]);

        $estatusAnterior = $pedido->estatus;
        $pedido->update([
            'estatus' => $request->estatus,
            'notas'   => $request->notas,
        ]);

        // Registrar en tracking
        TrackingPedido::create([
            'pedido_id'            => $pedido->id,
            'estatus'              => $request->estatus,
            'descripcion'          => "Cambio de '{$estatusAnterior}' a '{$request->estatus}'" . ($request->notas ? ". {$request->notas}" : ''),
            'fecha'                => now(),
            'usuario_responsable'  => session('proveedor_nombre', session('cliente_nombre', 'Sistema')),
        ]);

        // Notificar al cliente
        $cliente = ClienteUser::where('codigo_cliente', $pedido->codigo_cliente)->first();
        if ($cliente) {
            $this->notificaciones->notificarCambioPedido(
                [
                    'nombre'         => $cliente->nombre,
                    'correo'         => $cliente->correo,
                    'telefono'       => $cliente->telefono,
                    'codigo_cliente' => $cliente->codigo_cliente,
                ],
                $pedido->folio,
                $request->estatus,
                $request->notas,
            );
        }

        return back()->with('mensaje', "Pedido {$pedido->folio} actualizado a: {$request->estatus}");
    }

    /**
     * Consulta tracking de paquetería externa.
     */
    public function tracking(Request $request)
    {
        $request->validate([
            'carrier' => 'required|string|in:estafeta,dhl,fedex',
            'guia'    => 'required|string|max:50',
        ]);

        $resultado = $this->paqueteria->rastrear($request->carrier, $request->guia);

        return response()->json($resultado);
    }
}
