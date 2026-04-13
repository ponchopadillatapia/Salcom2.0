<?php

namespace App\Http\Controllers;

class PortalClienteController extends Controller
{
    public function mostrarPortal() { return view('clientes.portal'); }
    public function mostrarDashboard() { return view('clientes.dashboard'); }
    public function mostrarCatalogo() { return view('clientes.catalogo'); }
    public function mostrarPedidos() { return view('clientes.pedidos'); }
}
