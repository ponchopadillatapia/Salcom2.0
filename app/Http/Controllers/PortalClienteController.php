<?php

namespace App\Http\Controllers;

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
}
