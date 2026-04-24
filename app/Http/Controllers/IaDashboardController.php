<?php

namespace App\Http\Controllers;

use App\Models\ClienteUser;
use App\Models\Producto;
use App\Services\IaService;
use Illuminate\Http\Request;

class IaDashboardController extends Controller
{
    private IaService $iaService;

    public function __construct(IaService $iaService)
    {
        $this->iaService = $iaService;
    }

    // ══════════════════════════════════════════════
    //  ADMIN — dashboard IA con formularios
    // ══════════════════════════════════════════════

    public function adminIa()
    {
        return view('admin.ia-dashboard', [
            'clientes'  => $this->listaClientes(),
            'productos' => $this->listaProductos(),
        ]);
    }

    public function adminPronostico(Request $request)
    {
        $request->validate(['codigo_cliente' => 'required|string']);

        $resultado = $this->iaService->pronosticoDemanda($request->input('codigo_cliente'));

        return view('admin.ia-dashboard', [
            'clientes'            => $this->listaClientes(),
            'productos'           => $this->listaProductos(),
            'resultadoPronostico' => $resultado,
            'tabActiva'           => 'pronostico',
        ]);
    }

    public function adminInventario()
    {
        $resultado = $this->iaService->optimizacionInventario();

        return view('admin.ia-dashboard', [
            'clientes'            => $this->listaClientes(),
            'productos'           => $this->listaProductos(),
            'resultadoInventario' => $resultado,
            'tabActiva'           => 'inventario',
        ]);
    }

    public function adminProveedor(Request $request)
    {
        $request->validate(['producto_id' => 'required|string']);

        $resultado = $this->iaService->seleccionProveedor($request->input('producto_id'));

        return view('admin.ia-dashboard', [
            'clientes'           => $this->listaClientes(),
            'productos'          => $this->listaProductos(),
            'resultadoProveedor' => $resultado,
            'tabActiva'          => 'proveedor',
        ]);
    }

    // ══════════════════════════════════════════════
    //  PROVEEDOR — análisis automático al entrar
    // ══════════════════════════════════════════════

    public function proveedorIa()
    {
        $codigoProveedor = session('proveedor_codigo', 'PROV-001');

        $pronostico = $this->iaService->pronosticoDemanda($codigoProveedor);
        $inventario = $this->iaService->optimizacionInventario();
        $proveedor  = $this->iaService->seleccionProveedor('SAL-001');

        return view('proveedores.ia-dashboard', [
            'resultadoPronostico' => $pronostico,
            'resultadoInventario' => $inventario,
            'resultadoProveedor'  => $proveedor,
            'productos'           => $this->listaProductos(),
        ]);
    }

    // ══════════════════════════════════════════════
    //  CLIENTE — análisis automático al entrar
    // ══════════════════════════════════════════════

    public function clienteIa()
    {
        $codigoCliente = session('cliente_codigo', 'CLI-001');

        $pronostico = $this->iaService->pronosticoDemanda($codigoCliente);
        $inventario = $this->iaService->optimizacionInventario();

        return view('clientes.ia-dashboard', [
            'resultadoPronostico' => $pronostico,
            'resultadoInventario' => $inventario,
        ]);
    }

    // ══════════════════════════════════════════════
    //  Helpers — listas para selects
    // ══════════════════════════════════════════════

    private function listaClientes(): array
    {
        return ClienteUser::select('codigo_cliente as codigo', 'nombre')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    private function listaProductos(): array
    {
        return Producto::select('codigo as sku', 'nombre')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }
}
