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
            'clientes' => $this->listaClientes(),
            'productos' => $this->listaProductos(),
        ]);
    }

    public function adminPronostico(Request $request)
    {
        $request->validate(['codigo_cliente' => 'required|string']);

        $resultado = $this->iaService->pronosticoDemanda($request->input('codigo_cliente'));

        return view('admin.ia-dashboard', [
            'clientes' => $this->listaClientes(),
            'productos' => $this->listaProductos(),
            'resultadoPronostico' => $resultado,
            'tabActiva' => 'pronostico',
        ]);
    }

    public function adminInventario()
    {
        $resultado = $this->iaService->optimizacionInventario();

        return view('admin.ia-dashboard', [
            'clientes' => $this->listaClientes(),
            'productos' => $this->listaProductos(),
            'resultadoInventario' => $resultado,
            'tabActiva' => 'inventario',
        ]);
    }

    public function adminProveedor(Request $request)
    {
        $request->validate(['producto_id' => 'required|string']);

        $resultado = $this->iaService->seleccionProveedor($request->input('producto_id'));

        return view('admin.ia-dashboard', [
            'clientes' => $this->listaClientes(),
            'productos' => $this->listaProductos(),
            'resultadoProveedor' => $resultado,
            'tabActiva' => 'proveedor',
        ]);
    }

    // ══════════════════════════════════════════════
    //  PROVEEDOR — dashboard con botones (sin auto-load)
    // ══════════════════════════════════════════════

    public function proveedorIa()
    {
        return view('proveedores.ia-dashboard', [
            'productos' => $this->listaProductos(),
        ]);
    }

    public function proveedorPronostico()
    {
        $codigoProveedor = session('proveedor_codigo', 'PROV-001');
        $resultado = $this->iaService->pronosticoDemanda($codigoProveedor);

        return view('proveedores.ia-dashboard', [
            'productos' => $this->listaProductos(),
            'resultadoPronostico' => $resultado,
            'tabActiva' => 'pronostico',
        ]);
    }

    public function proveedorInventario()
    {
        $resultado = $this->iaService->optimizacionInventario();

        return view('proveedores.ia-dashboard', [
            'productos' => $this->listaProductos(),
            'resultadoInventario' => $resultado,
            'tabActiva' => 'inventario',
        ]);
    }

    public function proveedorProveedor(Request $request)
    {
        $productoId = $request->input('producto_id', 'SAL-001');
        $resultado = $this->iaService->seleccionProveedor($productoId);

        return view('proveedores.ia-dashboard', [
            'productos' => $this->listaProductos(),
            'resultadoProveedor' => $resultado,
            'tabActiva' => 'proveedor',
        ]);
    }

    // ══════════════════════════════════════════════
    //  CLIENTE — dashboard con botones (sin auto-load)
    // ══════════════════════════════════════════════

    public function clienteIa()
    {
        return view('clientes.ia-dashboard');
    }

    public function clientePronostico()
    {
        $codigoCliente = session('cliente_codigo', 'CLI-001');
        $resultado = $this->iaService->pronosticoDemanda($codigoCliente);

        return view('clientes.ia-dashboard', [
            'resultadoPronostico' => $resultado,
            'tabActiva' => 'pronostico',
        ]);
    }

    public function clienteInventario()
    {
        $resultado = $this->iaService->optimizacionInventario();

        return view('clientes.ia-dashboard', [
            'resultadoInventario' => $resultado,
            'tabActiva' => 'inventario',
        ]);
    }

    public function clienteDocumentacion()
    {
        $cliente = ClienteUser::find(session('cliente_id'));
        if (! $cliente) {
            abort(403);
        }

        $resultado = $this->iaService->validacionDocumentosCliente($cliente);

        return view('clientes.ia-dashboard', [
            'resultadoDocumentacion' => $resultado,
            'tabActiva' => 'documentacion',
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
