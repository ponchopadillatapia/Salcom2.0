<?php

namespace App\Http\Controllers;

use App\Services\IaService;
use Illuminate\Http\Request;

class IaDashboardController extends Controller
{
    private IaService $iaService;

    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    public function index()
    {
        $clientes  = $this->iaService->listarClientes();
        $productos = $this->iaService->listarProductos();

        return view('proveedores.ia-dashboard', compact('clientes', 'productos'));
    }

    public function pronosticoDemanda(Request $request)
    {
        $request->validate(['codigo_cliente' => 'required|string|max:50']);

        $resultado = $this->iaService->pronosticoDemanda($request->input('codigo_cliente'));
        $clientes  = $this->iaService->listarClientes();
        $productos = $this->iaService->listarProductos();

        return view('proveedores.ia-dashboard', [
            'clientes'            => $clientes,
            'productos'           => $productos,
            'resultadoPronostico' => $resultado,
            'tabActiva'           => 'pronostico',
        ]);
    }

    public function optimizacionInventario()
    {
        $resultado = $this->iaService->optimizacionInventario();
        $clientes  = $this->iaService->listarClientes();
        $productos = $this->iaService->listarProductos();

        return view('proveedores.ia-dashboard', [
            'clientes'             => $clientes,
            'productos'            => $productos,
            'resultadoInventario'  => $resultado,
            'tabActiva'            => 'inventario',
        ]);
    }

    public function seleccionProveedor(Request $request)
    {
        $request->validate(['producto_id' => 'required|string|max:50']);

        $resultado = $this->iaService->seleccionProveedor($request->input('producto_id'));
        $clientes  = $this->iaService->listarClientes();
        $productos = $this->iaService->listarProductos();

        return view('proveedores.ia-dashboard', [
            'clientes'            => $clientes,
            'productos'           => $productos,
            'resultadoProveedor'  => $resultado,
            'tabActiva'           => 'proveedor',
        ]);
    }
}
